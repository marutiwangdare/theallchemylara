<?php

namespace App\Http\Controllers;

use App\CPU\CartManager;
use App\CPU\Helpers;
use App\CPU\OrderManager;
use App\Model\Order;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class PhonepeController extends Controller
{
   

    //payment functions
    public function payment1(Request $request)
    {
        $order_id = Order::orderBy('id', 'DESC')->first()->id ?? 100001;
        $discount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        $value = CartManager::cart_grand_total() - $discount;
        $user = Helpers::get_customer();

        $paramList = array();
        $ORDER_ID = $order_id;
        $CUST_ID = $user['id'];
        $MOBILE_NUMBER = $user['phone'];
        $INDUSTRY_TYPE_ID = $request["INDUSTRY_TYPE_ID"];
        $CHANNEL_ID = $request["CHANNEL_ID"];
        $TXN_AMOUNT = round($value, 2);
        $AMOUNT_IN_PAISA = $TXN_AMOUNT * 100; //convert to paisa

        // Create an array having all required parameters for creating checksum.
        $paramList["MID"] = Config::get('config_paytm.PAYTM_MERCHANT_MID');
        $paramList["ORDER_ID"] = $ORDER_ID;
        $paramList["CUST_ID"] = $CUST_ID;
        $paramList["INDUSTRY_TYPE_ID"] = $INDUSTRY_TYPE_ID;
        $paramList["CHANNEL_ID"] = $CHANNEL_ID;
        $paramList["TXN_AMOUNT"] = $TXN_AMOUNT;
        $paramList["WEBSITE"] = Config::get('config_paytm.PAYTM_MERCHANT_WEBSITE');

        $paramList["CALLBACK_URL"] = route('paytm-response');
        $paramList["MSISDN"] = $user['phone']; //Mobile number of customer
        $paramList["EMAIL"] = $user['email']; //Email ID of customer
        $paramList["VERIFIED_BY"] = "EMAIL"; //
        $paramList["IS_USER_VERIFIED"] = "YES"; //

        $merchantTransactionId = 'MTID' . $ORDER_ID . date("Ymdhis");

        $_SESSION["merchantTransactionId"] = $merchantTransactionId;

        $config = Helpers::get_business_settings('phonepe');
        // merchantId = isset($config) && $config['status'];

        $data = [
            "merchantId" => Helpers::get_business_settings('phonepe'),
            "merchantTransactionId" => $merchantTransactionId,
            "merchantUserId" => $CUST_ID,
            "amount" => $AMOUNT_IN_PAISA,
            "redirectUrl" => redirectUrl,
            "redirectMode" => "POST",
            "callbackUrl" => callbackUrl,
            "mobileNumber" => MOBILE_NUMBER,
            "paymentInstrument" => [
                "type" => "PAY_PAGE"
            ]
        ];

        $body = base64_encode(json_encode($data));

        $raw = $body . apiEndpoint . saltKey;


        $XVERIFY = hash('sha256', $raw) . "###" . saltIndex;

        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', payApiUrl, [
            'body' => '{"request":"' . $body . '"}',
            'headers' => [
                'Content-Type' => 'application/json',
                'X-VERIFY' => $XVERIFY,
                'accept' => 'application/json',
            ],
        ]);


        $result = json_decode($response->getBody(), true);

        print_r($result);

        //Here checksum string will return by getChecksumFromArray() function.
        $checkSum = $this->getChecksumFromArray($paramList, Config::get('config_paytm.PAYTM_MERCHANT_KEY'));
        return view('paytm-payment-view', compact('checkSum', 'paramList'));
    }

    public function payment()
    {
        $config = Helpers::get_business_settings('phonepe');
        $phonepe_merchant_id = $config['phonepe_merchant_id'];
        $phonepe_merchant_salt_key = $config['phonepe_merchant_salt_key'];
        $phonepe_merchant_salt_index = $config['phonepe_merchant_salt_index'];
        $phonepe_merchant_api_url = $config['phonepe_merchant_api_url'];

        $order_id = Order::orderBy('id', 'DESC')->first()->id ?? 100001;
        $discount = session()->has('coupon_discount') ? session('coupon_discount') : 0;
        $value = CartManager::cart_grand_total() - $discount;
        $user = Helpers::get_customer();

        $cust_id = $user['id'];
        $phone = $user['phone'];
        $txn_amount = round($value, 2);
        $amount_in_paisa = $txn_amount * 100; //convert to paisa

        $data = array(
            'merchantId' =>  $phonepe_merchant_id,
            'merchantTransactionId' => (String)$order_id,
            'merchantUserId' => $cust_id,
            'amount' => $amount_in_paisa,
            'redirectUrl' => route('phonepe-response'),
            'redirectMode' => 'POST',
            'callbackUrl' => route('phonepe-response'),
            'mobileNumber' => $phone,
            'paymentInstrument' =>
            array(
                'type' => 'PAY_PAGE',
            ),
        );

        $encode = base64_encode(json_encode($data));
     
        $saltKey = $phonepe_merchant_salt_key;
        $saltIndex = $phonepe_merchant_salt_index;

        $string = $encode . '/pg/v1/pay' . $saltKey;
        $sha256 = hash('sha256', $string);

        $finalXHeader = $sha256 . '###' . $saltIndex;


        $url = $phonepe_merchant_api_url;

        $headers = [
            'Content-Type: application/json',
            'X-VERIFY: ' . $finalXHeader,
        ];

        $data = json_encode(['request' => $encode]);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            // Handle cURL error
            $error = curl_error($ch);  
        }

        //dd(($response));

        curl_close($ch);

        // Use $response as needed
        $rData = json_decode($response);
       // dd(json_decode($response));
        return redirect()->to($rData->data->instrumentResponse->redirectInfo->url);

    }

    public function callback(Request $request)
    {
        $input = $request->all();

        $config = Helpers::get_business_settings('phonepe');
        $phonepe_merchant_id = $config['phonepe_merchant_id'];
        $phonepe_merchant_salt_key = $config['phonepe_merchant_salt_key'];
        $phonepe_merchant_salt_index = $config['phonepe_merchant_salt_index'];
        $phonepe_merchant_api_url = $config['phonepe_merchant_api_url'];
        $phonepe_merchant_status_api_url = $config['phonepe_merchant_status_api_url'];

        $saltKey = $phonepe_merchant_salt_key;
        $saltIndex = $phonepe_merchant_salt_index;

        $finalXHeader = hash('sha256', '/pg/v1/status/' . $input['merchantId'] . '/' . $input['transactionId'] . $saltKey) . '###' . $saltIndex;

       $url = $phonepe_merchant_status_api_url . $input['merchantId'] . '/' . $input['transactionId'];

        $headers = [
            'Content-Type: application/json',
            'accept: application/json',
            'X-VERIFY: ' . $finalXHeader,
            'X-MERCHANT-ID: ' . $input['transactionId'],
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $response = json_decode($response);

        if (curl_errno($ch)) {
            // Handle cURL error
            $error = curl_error($ch);
        }

        curl_close($ch);

       // dd(($response));

        if ($response->success == 'true') {
            $unique_id = OrderManager::gen_unique_id();
            $order_ids = [];
            foreach (CartManager::get_cart_group_ids() as $group_id) {
                $data = [
                    'payment_method' => 'phonepe',
                    'order_status' => 'confirmed',
                    'payment_status' => 'paid',
                    'transaction_ref' => $response->data->transactionId,
                    'order_group_id' => $unique_id,
                    'cart_group_id' => $group_id
                ];
                $order_id = OrderManager::generate_order($data);
                array_push($order_ids, $order_id);
            }

            if (session()->has('payment_mode') && session('payment_mode') == 'app') {
                CartManager::cart_clean();
                return redirect()->route('payment-success');
            } else {
                CartManager::cart_clean();
                return view('web-views.checkout-complete');
            }
        }

        if (session()->has('payment_mode') && session('payment_mode') == 'app') {
            return redirect()->route('payment-fail');
        }
        Toastr::error('Payment process failed!');
        return back();
    }
}