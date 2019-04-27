<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>群发</title>
</head>
<body>
<table border="1" bgcolor="#c71585">
    <tr>
        <td><input type="checkbox" id="checkbox"></td>
        <td width="200" align="center"> id</td>
        <td width="200" align="center"> nickname</td>
        <td width="600" align="center">openid</td>
    </tr>
@foreach($data as $k=>$v)
    <tr>
        <td openid="{{$v->openid}}"><input type="checkbox" class="checkbox1"></td>
        <td width="200" align="center"> {{$v->id}}</td>
        <td width="200" align="center"> {{$v->nickname}}</td>
        <td width="600" align="center">{{$v->openid}}</td>
    </tr>
@endforeach
</table>
<div>发送的内容<input type="text" id="text"><button id="btn">群发</button></div>


</body>
</html>
<script type="text/javascript" src="/js/weixin/jquery-3.2.1.min.js"></script>
<script>
    $('#checkbox').click(function(){
        var type=$('#checkbox').prop('checked');
         $('.checkbox1').prop('checked',type);
    })
    $('.checkbox1').click(function(){
         if($(this).prop('checked')==false){
             $('#checkbox').prop('checked',false);2
         }
    })
    $('#btn').click(function(){
        var opid=$('.checkbox1');
        var text=$('#text').val();
        var openid='';
        opid.each(function(res){
            if($(this).prop('checked')==true) {
                openid += $(this).parent('td').attr('openid') + ',';
            }
        })
        openid=openid.substr(0,openid.length-1);
        if(openid==''){
            alert('请选择要发送的人');
            return false;
        }
        if(text==''){
            alert('请输入发送的内容');
            return false;
        }
        $.ajax({
            url:'MessageAdd/?openid='+openid+'&text='+text,
            type:'get'
        })
    })


</script>
