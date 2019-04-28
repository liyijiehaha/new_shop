<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
        {{$res->goods_id}}
        {{$res->goods_name}}
<div id="qrcode"></div>
</body>
</html>
<script src="/js/weixin/jquery-1.12.4.min.js"></script>
<script src="/js/weixin/qrcode.js"></script>
<script type="text/javascript">
    new QRCode(document.getElementById("qrcode"), "{{$code_url}}");
</script>
