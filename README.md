# aliyun-content-security
阿里云内容安全


## Installation
```
composer require tiway/aliyun-content-security:dev-master
```

## Quick Examples
```

$aliyun = new AliyunGreen('appid','se');
######### 异步检测  ###########
//单图
$urls = 'https://jialai-dev.oss-cn-hangzhou.aliyuncs.com/default_avatar/13.jpg'
//多图验证
$urls = [
            'https://jialai-dev.oss-cn-hangzhou.aliyuncs.com/default_avatar/13.jpg',
            'https://jialai-dev.oss-cn-hangzhou.aliyuncs.com/circle/2020-12-09/64516872-6091-4F92-811F-8AF297E9FE72_400×400.png'
        ];
$msg = $aliyun->imageAsyncScan($urls);

######### 同步检测  ###########
//单图
$urls = 'https://jialai-dev.oss-cn-hangzhou.aliyuncs.com/default_avatar/13.jpg'
//多图验证
$urls = [
            'https://jialai-dev.oss-cn-hangzhou.aliyuncs.com/default_avatar/13.jpg',
            'https://jialai-dev.oss-cn-hangzhou.aliyuncs.com/circle/2020-12-09/64516872-6091-4F92-811F-8AF297E9FE72_400×400.png'
        ];
$msg = $aliyun->scan($urls);

###### 检查结果 ######
$body = [
            'fdd25f95-4892-4d6b-aca9-7939bc6e9baa-1486198766695',
            'fdd25f95-4892-4d6b-aca9-7939bc6e9baa-1486198766695'
        ];
$urls = 'https://jialai-dev.oss-cn-hangzhou.aliyuncs.com/default_avatar/13.jpg';

$msg = $aliyun->scan($urls,AliyunGreen::TYPE_IMAGE,AliyunGreen::TYPE_IMAGE_DEFAULT);




```