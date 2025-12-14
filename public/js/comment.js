$(document).ready(function(){
    $("#report-form").find("textarea[name='message']").on("input",function(){
       
        var length=$(this).val().length;
        if(length<=500){
            $(".no-character").html(length);
        }else{
            return ;
        }
    });
   
    $("#report-form").submit(function(e){
        
        e.preventDefault();
        var index=0;
        var required_input=$(this).find(".required");
        $(required_input).each(function(key,input){
          
            if($(input).val().trim()==""){
                if($(this).next().is("span")==false){
                    $(this).after("<span class='text-danger'>"+$(input).attr("title")+" is required </span>");
                    $(this).on("input",function(){
                       $(this).next("span").remove();
                    });
                }
            }else{
                index++;
            }


        });
        //   alert(required_input.length);
        //   alert(index);
        if(required_input.length==index){
            var formadata=new FormData(this);
            var btn=$(this).find("button");
            $.ajax({
                type:"POST",
                url:"/my/report",
                data:formadata,
                contentType:false,
                processData:false,
                cache:false,
                beforeSend:function(){
                    $(btn).html("Please Wait...");
                    $(btn).attr("disabled",true);
                },
                success:function(response){
                    console.log(response);
                    $(btn).html("Submit");
                    $(btn).attr("disabled",false);
                 if(response.status==200){
                    $("#reportModal").modal("hide");
                   swal("Your query has been sent successfully !", "When your issues is resolved,You will be notified by email", "success");
                    $(".imageuploadify .well").html("");
                    $("#report-form").trigger("reset");
                 }else{
                    swal(response.message, "", "error");
                    $("#reportModal").modal("hide");
                    $("#report-form").trigger("reset");
                 }
    
                },error:function(response){
                    $(btn).html("Submit");
                    $(".imageuploadify .well").html("");
                    $(btn).attr("disabled",false);
                    if( response.status === 422 ) {
                        $(btn).html("Submit");
                    $(btn).attr("disabled",false);
                        $.each(response.responseJSON.errors,function(field_name,error){
                            console.log(field_name);
                            
                           if($("#report-form").find('[name='+field_name+']').next().is("span")==false){
                            $("#report-form").find('[name='+field_name+']').after('<span class="text-strong text-danger">' +error+ '</span>');
                           }
                        })
    
                        $("input").on("input",function(){
                           $(this).next("span").remove();
                        });
                        $("select").on("change",function(){
                            $(this).next("span").remove();
                         });
                         $("textarea").on("input",function(){
                            $(this).next("span").remove();
                         });
                    }
                }
            });
        }
        

    });

    //report modal hide time remove all error show in form
    $("#reportModal").on("hidden.bs.modal",function(){
    var input=$("#report-form").find("input");
    var select=$("#report-form").find("select");
    var textarea=$("#report-form").find("textarea");
    $("#report-form").trigger("reset");
    $(input).each(function(){
        $(this).next("span").remove();
    });
    $(select).each(function(){
        $(this).next("span").remove();
    });
    $(textarea).each(function(){
        $(this).next("span").remove();
    });
    });
});