<?php

namespace TransactionBundle\Utils\Provider;

use Doctrine\ORM\EntityManagerInterface;
use BeneficiaryBundle\Entity\Beneficiary;

/**
 * Class KHMFinancialProvider
 * @package TransactionBundle\Utils\Provider
 */
class KHMFinancialProvider extends DefaultFinancialProvider {

    /** @var EntityManagerInterface $em */
    private $em;

    /**
     * @var string
     */
    private $url = "https://stageonline.wingmoney.com:8443/RestEngine";
    /**
     * @var
     */
    private $token;
    /**
     * @var
     */
    private $lastTokenDate;

    /**
     * DefaultFinancialProvider constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * Get token to connect to API
     * @return object token
     * @throws \Exception
     */
    public function getToken()
    {
        $route = "/oauth/token";
        $body = array(
            "username"      => "thirdParty",
            "password"      => "ba0228f6e48ba7942d79e2b44e6072ee",
            "grant_type"    => "password",
            "client_id"     => "third_party",
            "client_secret" => "16681c9ff419d8ecc7cfe479eb02a7a",
            "scope"         => "trust"
        );
        
        try {
            $this->token = $this->sendRequest("POST", $route, $body);
            $this->lastTokenDate = new \DateTime();
            return $this->token;
        } catch (Exception $e) {
            throw $e;
        }
        
    }

    /**
     * Send money to beneficiaries
     * @param string $phoneNumber
     * @return object       transaction
     * @throws \Exception
     */
    public function sendMoneyToOne(string $phoneNumber = "0962620581")
    {
        $route = "/api/v1/sendmoney/nonwing/commit";
        $body = array(
            "amount"          => 50,
            "sender_msisdn"   => "012249184",
            "receivers_msisdn" => $phoneNumber
        );
        
        try {
            $sent = $this->sendRequest("POST", $route, $body);
            return $sent;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function getStatus()
    {
        $route = "/api/v1/sendmoney/nonwing/txn_inquiry";
        $body = array(
            "transaction_id" => "ABR590191"
        );
        
        try {
            $sent = $this->sendRequest("POST", $route, $body);
            return $sent;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Send request to WING API for Cambodia
     * @param  string $type type of the request ("GET", "POST", etc.)
     * @param  string $route url of the request
     * @param  array $body body of the request (optional)
     * @return mixed  response
     * @throws \Exception
     */
    public function sendRequest(string $type, string $route, array $body = array()) {
        $curl = curl_init();
        
        $headers = array();
        
        // Not authentication request
        if(!preg_match('/\/oauth\/token/', $route)) {
            if (!$this->lastTokenDate ||
            (new \DateTime())->getTimestamp() - $this->lastTokenDate->getTimestamp() > $this->token->expires_in) {
                $this->getToken();
            }
            array_push($headers, "Authorization: Bearer " . $this->token->access_token, "Content-type: application/json");
            $body = json_encode((object) $body);
        }
        // Authentication request
        else {
            $body = http_build_query($body); // Pass body as url-encoded string
        }
                
        curl_setopt_array($curl, array(
          CURLOPT_PORT           => "8443",
          CURLOPT_URL            => $this->url . $route,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING       => "",
          CURLOPT_MAXREDIRS      => 10,
          CURLOPT_TIMEOUT        => 30,
          CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST  => $type,
          CURLOPT_POSTFIELDS     => $body,
          CURLOPT_HTTPHEADER     => $headers
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        dump($response);
        dump($err);
        curl_close($curl);
    
        if ($err) {
            throw new \Exception($err);
        } else {
            $result = json_decode($response);
            if (property_exists($result, 'error_code')) {
                throw new \Exception($result['error_code'], $result['message']);
            }
            return $result;
        }
    }

}