<?php

namespace App\Http\Controllers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

use Automattic\WooCommerce\Client;

class ImportController extends Controller
{

    private $dev_name;
    private $cert_name;
    private $token;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->dev_name = env('BONANZA_DEV_NAME');
        $this->cert_name = env('BONANZA_CERT_NAME');
    }


    public function importProduct(Request $request)
    {

        $woocommerce = new Client(
            'https://wp-test.mydessk.com', 
            'ck_3a001821c2326169ad5f3acfc6273c4c241c4b25', 
            'cs_d6a21672e59ce2ca6e09745314568076e554d4f8',
            [
                'version' => 'wc/v3',
            ]
        );


       $this->token = $this->getToken();


       $items = collect($this->getItems($request->page,100));

       $categories = [];

       $products = $items->map(function($item) use ($woocommerce){

    

            $categories = [];

            $cat = explode(' >> ', $item['primaryCategory']['categoryName']);
            $aux = 0;
            foreach ($cat as $key => $c) {

                $categories[] = ['name' => $c];

            }       

            $data = [
                'type' => 'simple',
                'status' => 'publish',
                'name' => $item['title'],
                'description' => $item['description'],
                'sku' => ($item['sku'] == '') ? $item['title'] : $item['sku'] ,
                'regular_price' => $item['buyItNowPrice'],
                'sale_price' => $item['currentPrice'],
                'manage_stock' => true,
                'stock_quantity' => $item['quantity'],
                'images' => collect($item['pictureURL'])->map(function($image){return ['src' => $image];})->all(),
                //'categories' => $categories
                //'tax:product_cat' => implode('>', explode(' >> ', $item['primaryCategory']['categoryName']))
            ];

            /**

            if (isset($item['itemSpecifics'])) {
                foreach ($item['itemSpecifics'] as $key => $value) {
                    $data['meta: '.$value['nameValueList']['name']] = $value['nameValueList']['value']; 
                }
            }

            **/



            return $data;

        });


        foreach ($products as $key => $product) {

            $product = $woocommerce->post('products', $product);
    
        }

        echo "<pre>";
        print_r (count($products));
        echo "</pre>";

        return false;


        $keys = collect([]);

        foreach ($products as $key => $product) {
            $keys = $keys->concat(array_keys($product));
        }

        $keys = $keys->unique()->values()->all();


        $fileName = 'products-page-'.$request->page.'.csv';

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = $keys;

        $products = $products->map(function($product) use ($keys){
            foreach ($keys as $key) {
                if (!isset($product[$key])) {
                    $product[$key] = '';
                }
            }

            return $product;

        });

        return response()->json($products);

       
    }

    public function getItems($page = 1,$per_page = 5)
    {
        $url = "https://api.bonanza.com/api_requests/secure_request";
        $headers = array("X-BONANZLE-API-DEV-NAME: " . $this->dev_name, "X-BONANZLE-API-CERT-NAME: " . $this->cert_name);
        $args = array( 'userId' => 'GoldenGateEmporium', 'itemsPerPage' => $per_page, 'page' => $page);
        $args['requesterCredentials']['bonanzleAuthToken'] = $this->token;   // only necessary if specifying an itemStatus other than "for_sale"
        $request_name = "getBoothItemsRequest";
        $post_fields = "$request_name=" . json_encode($args) . " \n";
        
        $connection = curl_init($url);
        $curl_options = array(CURLOPT_HTTPHEADER=>$headers, CURLOPT_POSTFIELDS=>$post_fields,
                        CURLOPT_POST=>1, CURLOPT_RETURNTRANSFER=>1);  // data will be returned as a string
        curl_setopt_array($connection, $curl_options);
        $json_response = curl_exec($connection);
        if (curl_errno($connection) > 0) {
          echo curl_error($connection) . "\n";
          exit(2);
        }
        curl_close($connection);
        $response = json_decode($json_response,true);
        
        return $response['getBoothItemsResponse']['items'];

    }


    public function getToken()
    {
        

        $url = "https://api.bonanza.com/api_requests/secure_request";
        $headers = array("X-BONANZLE-API-DEV-NAME: " . $this->dev_name, "X-BONANZLE-API-CERT-NAME: " . $this->cert_name);
        $args = array();
        $post_fields = "fetchTokenRequest";
        $connection = curl_init($url);
        $curl_options = array(CURLOPT_HTTPHEADER=>$headers, CURLOPT_POSTFIELDS=>$post_fields,
                        CURLOPT_POST=>1, CURLOPT_RETURNTRANSFER=>1);  # data will be returned as a string
        curl_setopt_array($connection, $curl_options);
        $json_response = curl_exec($connection);
        if (curl_errno($connection) > 0) {
          echo curl_error($connection) . "\n";
          exit(2);
        }
        curl_close($connection);
        $response = json_decode($json_response,true);
        $token = $response['fetchTokenResponse']['authToken'];

        return $token;


    }


    //
}
