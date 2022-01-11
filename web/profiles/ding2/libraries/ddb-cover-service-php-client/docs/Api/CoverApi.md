# CoverService\CoverApi

All URIs are relative to */*

Method | HTTP request | Description
------------- | ------------- | -------------
[**getCoverCollection**](CoverApi.md#getcovercollection) | **GET** /api/v2/covers | Search multiple covers

# **getCoverCollection**
> \CoverService\Model\Cover[] getCoverCollection($type, $identifiers, $sizes)

Search multiple covers

Get covers by identifier in specific image format(s), specific image size(s) and with or without generic covers.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure OAuth2 access token for authorization: oauth
$config = CoverService\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');

$apiInstance = new CoverService\Api\CoverApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$type = "type_example"; // string | The type of the identifier, i.e. 'isbn', 'faust', 'pid' or 'issn'
$identifiers = array("identifiers_example"); // string[] | A list of identifiers of {type}. Maximum number os identifiers per reqeust is 200
$sizes = array("sizes_example"); // string[] | A list of image sizes (Cloudinary transformations) for the cover(s) you want to receive.

try {
    $result = $apiInstance->getCoverCollection($type, $identifiers, $sizes);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CoverApi->getCoverCollection: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **type** | **string**| The type of the identifier, i.e. &#x27;isbn&#x27;, &#x27;faust&#x27;, &#x27;pid&#x27; or &#x27;issn&#x27; |
 **identifiers** | [**string[]**](../Model/string.md)| A list of identifiers of {type}. Maximum number os identifiers per reqeust is 200 |
 **sizes** | [**string[]**](../Model/string.md)| A list of image sizes (Cloudinary transformations) for the cover(s) you want to receive. | [optional]

### Return type

[**\CoverService\Model\Cover[]**](../Model/Cover.md)

### Authorization

[oauth](../../README.md#oauth)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

