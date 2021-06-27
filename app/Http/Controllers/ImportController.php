<?php

namespace App\Http\Controllers;

use Automattic\WooCommerce\Client;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
            'ck_68a868735b54e805c43cdbad3fe1ccc8e9412a9d', 
            'cs_52945752439dc844cdd4a4b0c3e1d252e2757322',
            [
                'version' => 'wc/v3',
            ]
        ); 

       $this->token = $this->getToken();

       for ($i=1; $i < 6; $i++) { 

           $items = collect($this->getItems($i,500));


           $products = $items->map(function($item){

                $data = [
                    
                    'name' => $item['title'],
                    'description' => strip_tags($item['description'],'<ul><li><p><a>'),
                    'sku' => ($item['sku'] == '') ? $item['title'] : $item['sku'] ,
                    'regular_price' => $item['buyItNowPrice'],
                    'sale_price' => $item['currentPrice'],
                    'manage_stock' => true,
                    'stock_quantity' => $item['quantity'],
                    'images' => collect($item['pictureURL'])->map(function($image){return ['src' => $image];})->all(),
                ];

                /**

                $data = [
                    'sku' => ($item['sku'] == '') ? $item['title'] : $item['sku'] ,
                    'post_title' => $item['title'],
                    'tax:product_cat' => implode('>', explode(' >> ', $item['primaryCategory']['categoryName']))
                ];

                **/

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
               $wc_product = $woocommerce->get('products?sku='.urlencode($product['sku']))[0];

               try {
                   $woocommerce->put('products/'.$wc_product->id, $product);
                   Log::info("producto actualizado",["product" => $wc_product->id]);
               
               } catch (Exception $e) {
                   Log::info("fallo del cliente");
               }

               
           }

           /**


            $keys = collect([]);

            foreach ($products as $key => $product) {
                $keys = $keys->concat(array_keys($product));
            }

            $keys = $keys->unique()->values()->all();


            

            $columns = $keys;

            $products = $products->map(function($product) use ($keys){
                foreach ($keys as $key) {
                    if (!isset($product[$key])) {
                        $product[$key] = '';
                    }
                }


                return $product;

            });

            $fileName = 'products-page-'.$i.'-'.count($products).'.json';

            $fp = fopen($fileName, 'w');
            fwrite($fp, json_encode($products));
            fclose($fp);

            **/

        }

        


       
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
