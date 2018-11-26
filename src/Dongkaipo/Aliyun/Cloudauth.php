<?php
/**
 * Created by PhpStorm.
 * User: jack
 * Date: 2018/9/30
 * Time: 2:25 PM
 */

namespace Dongkaipo\Aliyun;

use Aliyun\Core\DefaultAcsClient;
use Aliyun\Core\Exception\ClientException;
use Aliyun\Core\Exception\ServerException;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\Regions\Endpoint;
use Aliyun\Core\Regions\EndpointProvider;
use Aliyun\Core\Regions\ProductDomain;
use Cloudauth\Request\V20180907\GetMaterialsRequest;
use Cloudauth\Request\V20180807\GetStatusRequest;
use Cloudauth\Request\V20180807\GetVerifyTokenRequest;
use Exception;

class Cloudauth
{

    private $client;
    private $biz;
    private $regions = [
        "cn-hangzhou", "cn-beijing", "cn-qingdao", "cn-hongkong", "cn-shanghai", "us-west-1", "cn-shenzhen", "ap-southeast-1"
    ];

    /**
     * Cloudauth constructor.
     * @param $regionId
     * @param $accessKeyId
     * @param $accessKeySecret
     * @param $biz 您在控制台上创建的、采用RPH5BioOnly认证方案的认证场景标识, 创建方法：https://help.aliyun.com/document_detail/59975.html
     */
    public function __construct($regionId, $accessKeyId, $accessKeySecret, $biz)
    {
        $productDomains = array(
            new ProductDomain("Ecs", "ecs.aliyuncs.com"),
            new ProductDomain("Rds", "rds.aliyuncs.com"),
            new ProductDomain("BatchCompute", "batchCompute.aliyuncs.com"),
            new ProductDomain("Bss", "bss.aliyuncs.com"),
            new ProductDomain("Oms", "oms.aliyuncs.com"),
            new ProductDomain("Slb", "slb.aliyuncs.com"),
            new ProductDomain("Oss", "oss-cn-hangzhou.aliyuncs.com"),
            new ProductDomain("OssAdmin", "oss-admin.aliyuncs.com"),
            new ProductDomain("Sts", "sts.aliyuncs.com"),
            new ProductDomain("Yundun", "yundun-cn-hangzhou.aliyuncs.com"),
            new ProductDomain("Risk", "risk-cn-hangzhou.aliyuncs.com"),
            new ProductDomain("Drds", "drds.aliyuncs.com"),
            new ProductDomain("M-kvstore", "m-kvstore.aliyuncs.com"),
            new ProductDomain("Ram", "ram.aliyuncs.com"),
            new ProductDomain("Cms", "metrics.aliyuncs.com"),
            new ProductDomain("Crm", "crm-cn-hangzhou.aliyuncs.com"),
            new ProductDomain("Ocs", "pop-ocs.aliyuncs.com"),
            new ProductDomain("Ots", "ots-pop.aliyuncs.com"),
            new ProductDomain("Dqs", "dqs.aliyuncs.com"),
            new ProductDomain("Location", "location.aliyuncs.com"),
            new ProductDomain("Ubsms", "ubsms.aliyuncs.com"),
            new ProductDomain("Ubsms-inner", "ubsms-inner.aliyuncs.com")
        );
        $endpoint = new Endpoint("cn-beijing", $this->regions, $productDomains);
        $endpoints = array($endpoint);
        EndpointProvider::setEndpoints($endpoints);


        // 创建DefaultAcsClient实例并初始化
        $iClientProfile = DefaultProfile::getProfile($regionId, $accessKeyId, $accessKeySecret);
        $iClientProfile::addEndpoint($regionId, $regionId, "Cloudauth", "cloudauth.aliyuncs.com");
        $this->client = new DefaultAcsClient($iClientProfile);
        $this->biz = $biz;
    }


    /**
     * @param $ticketId 认证ID, 由使用方指定, 发起不同的认证任务需要更换不同的认证ID
     * @param $name 认证人姓名
     * @param $IDNum 认证人身份证
     * @param bool $IDCardRequired 若需要binding图片(如身份证正反面等), 且使用base64上传, 需要设置为 true ,图片限制在 2M 以内
     * @return null|\SimpleXMLElement
     * @throws Exception
     */
    public function token($ticketId, $name, $IDNum, $IDCardRequired = false)
    {
        $token = null; //认证token, 表达一次认证会话
        $getVerifyTokenRequest = new GetVerifyTokenRequest();
        $getVerifyTokenRequest->setBiz($this->biz);
        $getVerifyTokenRequest->setTicketId($ticketId);
        if ($IDCardRequired) {
            $getVerifyTokenRequest->setMethod("POST");
        }
        $getVerifyTokenRequest->setBinding("{\"Name\": \"".$name."\",\"IdentificationNumber\": \"".$IDNum."\"}");
        try {
            $response = $this->client->getAcsResponse($getVerifyTokenRequest);
        } catch (Exception $exception) {
            throw $exception;
        }
        return $response;
    }

    public function getRedirectUrl($token)
    {


    }

    /**
     * @param $ticketId
     * @return int|\SimpleXMLElement
     * @throws ClientException
     * @throws ServerException
     */
    public function getStatus($ticketId)
    {
        $statusCode = -1; //-1 未认证, 0 认证中, 1 认证通过, 2 认证不通过
        $getStatusRequest = new GetStatusRequest();
        $getStatusRequest->setBiz($this->biz);
        $getStatusRequest->setTicketId($ticketId);
        try {
            $response = $this->client->getAcsResponse($getStatusRequest);
            $statusCode = $response->Data->StatusCode;
        } catch (ServerException $exception) {
            throw $exception;
        } catch (ClientException $exception) {
            throw $exception;
        }
        return $statusCode;
    }

    /**
     * @param $ticketId
     * @return int|mixed|\SimpleXMLElement
     * @throws ClientException
     * @throws ServerException
     */
    public function getVerifyInfo($ticketId)
    {
        try {
            $statusCode = $this->getStatus($ticketId);
        } catch (Exception $exception) {
            throw $exception;
        }

        $getMaterialsRequest = new GetMaterialsRequest();
        $getMaterialsRequest->setBiz($this->biz);
        $getMaterialsRequest->setTicketId($ticketId);
        if (1 == $statusCode or 2 == $statusCode) { //认证通过or认证不通过
            try {
                $response = $this->client->getAcsResponse($getMaterialsRequest);
            } catch (ServerException $exception) {
                throw $exception;
            } catch (ClientException $exception) {
                throw $exception;
            }
            return $response;
        }
        return $statusCode;
    }

}
