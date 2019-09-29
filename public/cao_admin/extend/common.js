/**
 * ajax POST请求
 * @param url
 * @param data
 * @param success
 */
function http_post(url,data,success,error = null) {
    $.ajax({
        url,
        method: "POST",
        data,
        success:(res) => {
            if(res.code != 1) {
                if(error) {
                    layer.msg(res.msg, {icon: 5},error(res));
                }else{
                    layer.msg(res.msg, {icon: 5});
                }
            }else{
                success(res.data);
            }
        }
    })
}

// 弹出iframe层
function pop_iframe(title,url,is_max = true,option = {}) {
    var op = {
        type: 2,
        title,
        content: url,
    };
    op = $.extend(op,option);
    var index = layer.open(op);
    if(is_max) {
        layer.full(index);
    }
}


$(function () {
    // 文件上传点击事件
    $(".file-area .select").on("click",function(){
        $(this).parents('.file-pretty').find("input[type='file']").trigger('click');
    });

    // 文件异步上传
    $(".file-area input[type='file']").on("change",function(){
        var formData = new FormData();
        var url = $(this).attr('up-url');
        if(!url) {
            layer.msg('请指定上传服务器地址', {icon: 5});
            return;
        }
        formData.append('upload_file', this.files[0]);
        $.ajax({
            url,
            type: "POST",
            data: formData,
            /**
             *必须false才会自动加上正确的Content-Type
             */
            contentType: false,
            /**
             * 必须false才会避开jQuery对 formdata 的默认处理
             * XMLHttpRequest会对 formdata 进行正确的处理
             */
            processData: false,
            success: (res) => {
                if(res.code != 1) {
                    layer.msg(res.msg, {icon: 5});
                }else{
                    $(this).parents('.file-pretty').find("input[type='text']").val(res.data);
                    $(this).parents('.file-pretty').find(".beforeView").attr('src',res.data);
                }
            },
            error: function () {
                layer.msg('网络请求失败',{icon:5})
            }
        });
    });

    // 查看图片
    $(".file-area .back-change").on("click",function(){
        var img = $(this).parents('.file-area').find("input[type='text']").val();
        if(img) {
            layer.open({
                type: 1,
                title: null,
                content: '<img src="'+img+'" style="width: 100%;height: 100%;object-fit: cover">',
                maxWidth: 600,
                maxHeight: 600,
            })
        }else{
            layer.msg('请上传图片',{icon:5})
        }
    });

    // 全选
    $('#check_all').on('ifChecked ifUnchecked', function(e){
        if(e.type == 'ifChecked') {
            $("table .i-checks").iCheck('check')
        }else{
            $("table .i-checks").iCheck('uncheck')
        }
    });


    // 开关切换
    $(".onoffswitch input[type='checkbox']").on("change",function(){
        var url = $(this).attr('data-url');
        var id = $(this).attr('data-id');
        var cur = $(this).prop('checked');
        var _this = this;
        console.log(url);
        if(!url) {
            return;
        }
        layer.confirm('确定修改此状态吗?', {icon: 3, title:'提示'}, function(index){
            http_post(url,{id},function (data) {
                layer.msg('修改成功', {icon: 1});
            },function (res) {
                $(_this).prop('checked',!cur);
            });
            layer.close(index);
        },function (index) {
            $(_this).prop('checked',!cur);
            layer.close(index);
        });
    });

    // 批量删除
    $('#delete_more').on('click', function(){
        var id = [];
        $("input[name='id[]']:checkbox").each(function() {
            if (true == $(this).is(':checked')) {
                id.push(parseInt($(this).val()))
            }
        });
        if(id.length == 0) {
            layer.msg('请选择需要删除的记录');
            return;
        }
        layer.confirm('确定删除选中记录吗?', {icon: 3, title:'提示'}, function(index){
            $.ajax({
                url:$('#check_ids').attr('action'),
                type:'post',
                data:{id},
                success:function(res){
                    if(res.code != 1){
                        layer.msg(res.msg, {icon: 5});
                    }else{
                        layer.msg('删除成功', {icon: 1},function () {
                            location.reload();
                        });
                    }
                }
            });
            layer.close(index);
        });
    });

    // 单个删除
    $('.delete-one').on('click',function () {
        var _this = this;
        layer.confirm('确定删除此记录吗?', {icon: 3, title:'提示'}, function(index) {
            var url = $(_this).attr('data-url');
            var id = $(_this).attr('data-id');
            if (url && id) {
                http_post(url, {id}, function (res) {
                    layer.msg('删除成功', {icon: 1}, function () {
                        location.reload();
                    });
                }, function (res) {
                    layer.msg(res.msg, {icon: 5});
                })
            } else {
                layer.msg('路由或者ID为空', {icon: 5});
            }
            layer.close(index);
        });

    })

});
