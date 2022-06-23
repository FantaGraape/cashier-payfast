<?php

namespace EllisSystems\Payfast\Http\Middleware;

use Closure;
use PayFast\PayFastApi;
use PayFast\PayFastPayment;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Support\Facades\Log;

/**
 * @see https://developers.payfast.co.za/docs#step_4_confirm_payment
 */
class VerifyWebhookNotification
{
    public function handle($request, Closure $next)
    {
        Log::debug('starting...');
        if (!$this->validateReffererOrigin($request)) {
            return false;
        }

        $payload = $request->all();
        $parameterString = '';
        foreach ($payload as $key => $val) {
            $payload[$key] = stripslashes($val);
        }
        foreach ($payload as $key => $val) {
            if ($key !== 'signature') {
                $parameterString .= $key . '=' . urlencode($val) . '&';
            } else {
                break;
            }
        }
        $parameterString = substr($parameterString, 0, -1);
        $validSignature = $this->validateSignature($payload, $parameterString);
        $validPaymentData = $this->validatePaymentData($payload);
        $serverConfirmation = $this->getServerConfirmation($parameterString);
        if ($validSignature && $validPaymentData && $serverConfirmation) {
            Log::debug('Baby we are cooking now');
            return $next($request);
        }
    }

    private function validateReffererOrigin($request)
    {

        $validHosts = array(
            'www.payfast.co.za',
            'sandbox.payfast.co.za',
            'w1w.payfast.co.za',
            'w2w.payfast.co.za',
        );

        $validIps = [];

        foreach ($validHosts as $validHost) {
            $ips = gethostbynamel($validHost);
            if ($ips !== false) {
                $validIps = array_merge($validIps, $ips);
            }
        }

        $validIps = array_unique($validIps);

        $reffererIpAddress = $request->ip();

        if (config('cashier.sandbox')) {
            $localhost = array('127.0.0.1');
            $validIps = array_merge($validIps, $localhost);
        }

        if (in_array($reffererIpAddress, $validIps, true)) {
            Log::debug('VALID REFER');
            return true;
        }
        Log::debug('INVALID REFER');
        return false;
    }

    private function validateSignature($payload, $parameterString)
    {

        if (!isset($payload['signature'])) {
            PayFastPayment::$errorMsg[] = "Invalid signature";
            return false;
        }
        $payfastPassphrase = config('payfast.passphrase');
        //opinionated return, passphrase should always be set for security concerns
        if ($payfastPassphrase === null) {
            PayFastPayment::$errorMsg[] = "Invalid passphrase";
            return false;
        }

        $serverSideParamString = $parameterString . '&passphrase=' . urlencode($payfastPassphrase);
        $serverSideSignature = md5($serverSideParamString);
        if ($payload['signature'] !== $serverSideSignature) {
            PayFastPayment::$errorMsg[] = "Invalid signature";
            return false;
        }

        return ($payload['signature'] === $serverSideSignature);
    }

    private function validatePaymentData($payload)
    {
        $cartTotal = 200; // TESTING
        //Check DB if unique payment ID exists
        //Check that payment cartTotal is within 0.01 of payload["amount_gross"]
        return !(abs((float)$cartTotal - (float)$payload['amount_gross']) > 0.01);
    }

    private function getServerConfirmation($parameterString)
    {
        $payFastProxy = config('cashier.proxy');
        $payFastHost = config('cashier.sandbox') ? 'sandbox.payfast.co.za' : 'www.payfast.co.za';
        if (in_array('curl', get_loaded_extensions(), true)) {
            // Variable initialization
            $url = 'https://' . $payFastHost . '/eng/query/validate';

            // Create default cURL object
            $ch = curl_init();

            // Set cURL options - Use curl_setopt for greater PHP compatibility
            // Base settings
            curl_setopt($ch, CURLOPT_USERAGENT, NULL);  // Set user agent
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);      // Return output as string rather than outputting it
            curl_setopt($ch, CURLOPT_HEADER, false);             // Don't include header in output
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            // Standard settings
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameterString);
            if (!empty($payFastProxy))
                curl_setopt($ch, CURLOPT_PROXY, $payFastProxy);

            // Execute cURL
            $response = curl_exec($ch);
            curl_close($ch);
            if ($response === 'VALID') {
                return true;
            }
        }
        return false;
    }
    /*
    -------
    ((Step1))
    Check if notification has come from a valid payfast domain
     $validHosts = array(
        'www.payfast.co.za',
        'sandbox.payfast.co.za',
        'w1w.payfast.co.za',
        'w2w.payfast.co.za',
        );
    Create array of valid IPs and check each hostname for valid IPs
    append to array each trusted IP
    Remove duplicate IPs
    Get the IP of host that sent the payload
    Check if IP of host exists in the ValidIP array
    If not then fail the process
    if valid then continue to Step2
    ---------
    ((Step2))
    Receive payload from payfast
    Check if Payfast Passphrase (PayFastPassphrase) isset in ENV
    if !PayFastPassphrase
        temporaryParameterString = Payload.ParameterString
    else
        //Add Payfast Passphrase to the Parameter String in preparation of md5 hashing and confirming hash
        temporaryParameterString = Payload.ParameterString + .'&passphrase='.urlencode( $PayfastPassphrase)
    MD5 Hash the temporaryParameterString to $signature
    Check if Payload.siganture ===  $signature
    if not then fail the process
    if valid then continue to Step3
    -------
    ((Step3))
    Check that payload data 'amount_gross' is within 0.01 tolerance of cartTotal of pending transaction
    if not then fail the process
    if valid then continue to Step4
    -------
    ((Step4))
    Send the payload parameter string back to payfast to validate conclusively that the order details are the ones received by payfast
    if not valid then fail the process
    if valid then process 
    -------

     */
}
