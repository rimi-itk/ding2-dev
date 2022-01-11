# CoverServiceUpload\CoverApi

All URIs are relative to */*

Method | HTTP request | Description
------------- | ------------- | -------------
[**getCoverCollection**](CoverApi.md#getcovercollection) | **GET** /api/covers | Retrieves the collection of Cover resources.
[**getCoverItem**](CoverApi.md#getcoveritem) | **GET** /api/covers/{id} | Retrieves a Cover resource.
[**postCoverCollection**](CoverApi.md#postcovercollection) | **POST** /api/covers | Creates a Cover resource.

# **getCoverCollection**
> \CoverServiceUpload\Model\CoverRead[] getCoverCollection()

Retrieves the collection of Cover resources.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure OAuth2 access token for authorization: oauth
$config = CoverServiceUpload\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');

$apiInstance = new CoverServiceUpload\Api\CoverApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->getCoverCollection();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CoverApi->getCoverCollection: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters
This endpoint does not need any parameter.

### Return type

[**\CoverServiceUpload\Model\CoverRead[]**](../Model/CoverRead.md)

### Authorization

[oauth](../../README.md#oauth)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json, text/html

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getCoverItem**
> \CoverServiceUpload\Model\CoverRead getCoverItem($id)

Retrieves a Cover resource.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure OAuth2 access token for authorization: oauth
$config = CoverServiceUpload\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');

$apiInstance = new CoverServiceUpload\Api\CoverApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = "id_example"; // string | 

try {
    $result = $apiInstance->getCoverItem($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CoverApi->getCoverItem: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **id** | **string**|  |

### Return type

[**\CoverServiceUpload\Model\CoverRead**](../Model/CoverRead.md)

### Authorization

[oauth](../../README.md#oauth)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json, text/html

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **postCoverCollection**
> \CoverServiceUpload\Model\CoverRead postCoverCollection($cover)

Creates a Cover resource.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure OAuth2 access token for authorization: oauth
$config = CoverServiceUpload\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');

$apiInstance = new CoverServiceUpload\Api\CoverApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$cover = "cover_example"; // string | 

try {
    $result = $apiInstance->postCoverCollection($cover);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling CoverApi->postCoverCollection: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **cover** | **string****string**|  | [optional]

### Return type

[**\CoverServiceUpload\Model\CoverRead**](../Model/CoverRead.md)

### Authorization

[oauth](../../README.md#oauth)

### HTTP request headers

 - **Content-Type**: multipart/form-data
 - **Accept**: application/json, text/html

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

