# CoverServiceUpload\MaterialApi

All URIs are relative to */*

Method | HTTP request | Description
------------- | ------------- | -------------
[**deleteMaterialItem**](MaterialApi.md#deletematerialitem) | **DELETE** /api/materials/{id} | Removes the Material resource.
[**getMaterialCollection**](MaterialApi.md#getmaterialcollection) | **GET** /api/materials | Retrieves the collection of Material resources.
[**getMaterialItem**](MaterialApi.md#getmaterialitem) | **GET** /api/materials/{id} | Retrieves a Material resource.
[**postMaterialCollection**](MaterialApi.md#postmaterialcollection) | **POST** /api/materials | Creates a Material resource.

# **deleteMaterialItem**
> deleteMaterialItem($id)

Removes the Material resource.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure OAuth2 access token for authorization: oauth
$config = CoverServiceUpload\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');

$apiInstance = new CoverServiceUpload\Api\MaterialApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = "id_example"; // string | 

try {
    $apiInstance->deleteMaterialItem($id);
} catch (Exception $e) {
    echo 'Exception when calling MaterialApi->deleteMaterialItem: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **id** | **string**|  |

### Return type

void (empty response body)

### Authorization

[oauth](../../README.md#oauth)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: Not defined

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getMaterialCollection**
> \CoverServiceUpload\Model\MaterialRead[] getMaterialCollection()

Retrieves the collection of Material resources.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure OAuth2 access token for authorization: oauth
$config = CoverServiceUpload\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');

$apiInstance = new CoverServiceUpload\Api\MaterialApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);

try {
    $result = $apiInstance->getMaterialCollection();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling MaterialApi->getMaterialCollection: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters
This endpoint does not need any parameter.

### Return type

[**\CoverServiceUpload\Model\MaterialRead[]**](../Model/MaterialRead.md)

### Authorization

[oauth](../../README.md#oauth)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json, text/html

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **getMaterialItem**
> \CoverServiceUpload\Model\MaterialRead getMaterialItem($id)

Retrieves a Material resource.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure OAuth2 access token for authorization: oauth
$config = CoverServiceUpload\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');

$apiInstance = new CoverServiceUpload\Api\MaterialApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = "id_example"; // string | 

try {
    $result = $apiInstance->getMaterialItem($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling MaterialApi->getMaterialItem: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **id** | **string**|  |

### Return type

[**\CoverServiceUpload\Model\MaterialRead**](../Model/MaterialRead.md)

### Authorization

[oauth](../../README.md#oauth)

### HTTP request headers

 - **Content-Type**: Not defined
 - **Accept**: application/json, text/html

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

# **postMaterialCollection**
> \CoverServiceUpload\Model\MaterialRead postMaterialCollection($body)

Creates a Material resource.

### Example
```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Configure OAuth2 access token for authorization: oauth
$config = CoverServiceUpload\Configuration::getDefaultConfiguration()->setAccessToken('YOUR_ACCESS_TOKEN');

$apiInstance = new CoverServiceUpload\Api\MaterialApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$body = new \CoverServiceUpload\Model\MaterialWrite(); // \CoverServiceUpload\Model\MaterialWrite | The new Material resource

try {
    $result = $apiInstance->postMaterialCollection($body);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling MaterialApi->postMaterialCollection: ', $e->getMessage(), PHP_EOL;
}
?>
```

### Parameters

Name | Type | Description  | Notes
------------- | ------------- | ------------- | -------------
 **body** | [**\CoverServiceUpload\Model\MaterialWrite**](../Model/MaterialWrite.md)| The new Material resource | [optional]

### Return type

[**\CoverServiceUpload\Model\MaterialRead**](../Model/MaterialRead.md)

### Authorization

[oauth](../../README.md#oauth)

### HTTP request headers

 - **Content-Type**: application/json, text/html
 - **Accept**: application/json, text/html

[[Back to top]](#) [[Back to API list]](../../README.md#documentation-for-api-endpoints) [[Back to Model list]](../../README.md#documentation-for-models) [[Back to README]](../../README.md)

