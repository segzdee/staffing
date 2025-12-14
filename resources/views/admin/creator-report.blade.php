@extends('admin.layout')

@section('css')
<link href="{{ asset('plugin/css/lightgallery.css') }}" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
  />
<style>.row1 {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
}

.column {
  flex: 33.33%;
  padding: 5;
  width: fitc-nytent;
}

.modal-confirm {		
	color: #636363;
	width: 400px;
  top:20%;
}
.modal-confirm .modal-content {
	padding: 20px;
	border-radius: 5px;
	border: none;
	text-align: center;
	font-size: 14px;
  box-shadow: 0px 0px 5px #ccc;
}
.modal-confirm .modal-header {
	border-bottom: none;   
	position: relative;
}
.modal-confirm h4 {
	text-align: center;
	font-size: 26px;
	margin: 30px 0 -10px;
  color: black;
  font-family: 'Poppins', sans-serif;
  font-weight: 900;
}
.modal-confirm .close {
	position: absolute;
	top: -5px;
	right: -2px;
}
.modal-confirm .modal-body {
	color: #999;
}
.modal-confirm .modal-footer {
	border: none;
	text-align: center;		
	border-radius: 5px;
	font-size: 13px;
	padding: 10px 15px 25px;
}
.modal-confirm .modal-footer a {
	color: #999;
}		
.modal-confirm .icon-box {
	width: 80px;
	height: 80px;
	margin: 0 auto;
	border-radius: 50%;
	z-index: 9;
	text-align: center;
	border: 3px solid green;
}
.modal-confirm .icon-box i {
	color: green;
	font-size: 46px;
	display: inline-block;
	margin-top: 13px;
}
.modal-confirm .btn, .modal-confirm .btn:active {
	color: #fff;
	border-radius: 4px;
	background: #60c7c1;
	text-decoration: none;
	transition: all 0.4s;
	line-height: normal;
	min-width: 120px;
	border: none;
	min-height: 40px;
	border-radius: 3px;
	margin: 0 5px;
  font-family: 'Poppins', sans-serif;
}
.modal-confirm .btn-secondary {
	background: #c1c1c1;
  font-family: 'Poppins', sans-serif;
}
.modal-confirm .btn-secondary:hover, .modal-confirm .btn-secondary:focus {
	background: #a8a8a8;
  font-family: 'Poppins', sans-serif;
}
.modal-confirm .btn-danger {
	background: #f15e5e;
  font-family: 'Poppins', sans-serif;
}
.modal-confirm .btn-danger:hover, .modal-confirm .btn-danger:focus {
	background: #ee3535;
  font-family: 'Poppins', sans-serif;
}
.trigger-btn {
	display: inline-block;
	margin: 100px auto;
  font-family: 'Poppins', sans-serif;
}
.status-change{
  background-color: #a8a8a8;
}
.icon-btn{
  width:45px !important;
  height: 45px !important;;
  border-radius: 50% !important;
  text-align: center;
  padding: 4px 2px;
  font-size: 12px !important;
}
.icon-btn span{
  font-size: 20px !important;
}

</style>

@endsection

@section('content')
<!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
          <h4>
            {{ trans('admin.admin') }}
            	<i class="fa fa-angle-right margin-separator"></i>
            		{{-- {{ trans('general.blog') }} --}}
                    Creator Report
            			<i class="fa fa-angle-right margin-separator"></i>
            				Creator Report List
                  </h4>
                </section>

        <!-- Main content -->
       
        <section class="content">

        	<div class="content">

        		<div class="row">
              @if(session()->has("success_message"))
              <div class="alert alert-success"><i class="fa fa-close close" data-dismiss='alert'></i> {{session()->get("success_message")}}</div>
              @endif
              @if(session()->has("error_message"))
              <div class="alert alert-warning"><i class="fa fa-close close" data-dismiss='alert'></i> {{session()->get("error_message")}}</div>
              @endif
        	<div class="box">
                <!-- form start -->
                <tr>
                <div class="box-body table-responsive no-padding">
                    <table class="table  text-center">
                        <tr style="background-color: aliceblue;padding:10px 0px;">
                            <th>
                                Sr.No
                            </th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Title</th>
                            <th>Photo</th>
                            
                            <th>Message</th>
                            <th>Status</th>
                            <th>Report Date</th>
                            <th>Action</th>
                        </tr>
                         @if(count($data)>0)
                         @foreach ($data as $key=>$item)
                         <tr>
                             <td  width="2%">{{$key+1}}</td>
                             <td  width="2%">{{$item['users']['username']}}</td>
                             <td  width="2%">{{$item['users']['name']}}</td>
                             <td  width="10%">{{$item['title']}}</td>
                             <td style="width: 20%">
                              @if($item['image'])
                              <center><ul id="lightgallery" class="list-unstyled row lightgallery  d-flex lightgallery">
                                 
                               @foreach (json_decode($item['image'],true) as $index=> $image)
                               <li class="col-1 column"  data-src="{{url('storage/uploads/bug-image/'.$image)}}" data-responsive-src="{{url('storage/uploads/bug-image/'.$image)}}" data-src="img1.jpg" style="margin-bottom: 2px !important;float:left;margin-left:5px;" data-html="{{$item['description']}}" >
                              
                                  <a href="#">
                             
                                    <img class="img-responsive" src="{{url('storage/uploads/bug-image/'.$image)}}" width="50">
                             
                                  </a>
                             
                                </li>
                                   
                               @endforeach
                           </ul></center>
                                 
                              @else
                              <center><img src="{{url('no-img.png')}}" alt="no image" width="70"></center>
                              @endif
                             </td>
                             <td width="20%">
                             
                                 {!! Str::limit($item['message'], 30, ' ...') !!} 
                             </td>
                             <td>
                               <div class="dropdown">
                                 <select name="" id="status-change" class="form-control status-change" url="{{url('/panel/admin/creator-report/status')}}/{{$item['id']}}/{{$item['status']}}">
                                  
                                   <option value="solve" {{$item['status']=="resolve"?"selected":""}}>Resolve</option>
                                   <option value="pending" {{$item['status']=="pending"?"selected":""}}>Pending</option>
                                 </select>
                               </div>
                                 {{-- @if($item['status']=="pending")
                                <a href="{{url('/panel/admin/creator-report/status')}}/{{$item['id']}}/{{$item['status']}}"> <span class="label label-warning" style="cursor:pointer;padding:4px 10px">Pending</span></a>
                                 @else
                                 <a href="{{url('/panel/admin/creator-report/status')}}/{{$item['id']}}/{{$item['status']}}"><span class="label label-success" style="cursor:pointer;padding:4px 10px">Solve</span></a>
                                 @endif --}}
                             </td>
 
                             <td>{{\Carbon\Carbon::parse($item['created_at'])->format("d-m-Y")}}</td>
 
                             <td class="d-flex">
                                 <button class="btn btn-info icon-btn email-btn" url="{{url('/panel/admin/creator-report')}}/email/{{$item['id']}}/{{$item['users']['id']}}"><span class="material-symbols-outlined">
                                   mail
                                   </span></button>
                                <button class="btn btn-danger icon-btn delete-btn" url="{{url('/panel/admin/creator-report')}}/delete/{{$item['id']}}"><span class="material-symbols-outlined">
                                   delete
                                   </span></button>
                             </td>
                         </tr>
                             
                         @endforeach
                         @else
                         <tr>

                          <td colspan="9">
                            <center><h4>No Any data found</h4></center>
                          </td>
                         </tr>

                         @endif
                       </table>
                       {{$data->links()}}
                </div>
              
              </div>

        		</div><!-- /.row -->

        	</div><!-- /.content -->

          <!-- Your Page Content Here -->

        </section><!-- /.content -->
      </div><!-- /.content-wrapper -->
      <!--- confirm modal --------->
      <div id="myModal" class="modal confirm-modal animate__animated animate__fadeIn">
        <div class="modal-dialog modal-confirm modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header flex-column">
              <div class="icon-box">
                <i class="material-symbols-outlined">
                  check
                </i>
              </div>						
              <h4 class="modal-title w-100">Are you sure?</h4>	
                      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>
            <div class="modal-body">
             
              <p style="color:black;font-family: 'Poppins', sans-serif;font-weight:700" class="modal-para">Do you really want to change  status</p>
            </div>
            <div class="modal-footer justify-content-center">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
              <button type="button" class="btn btn-danger confirm-btn">Yes</button>
            </div>
          </div>
        </div>
      </div> 
      <!---end confirm modal ------------->
@endsection

@section('javascript')
<script src="{{ url('plugin/js/lightgallery-all.js')}}"></script>
<script>
    
$(document).ready(function(){

  $('.lightgallery').lightGallery();

  $(".status-change").change(function(){
    var url=$(this).attr("url");
    var value=$(this).val();
     $(".modal-para").html("Do you really want to change  status");
    $(".confirm-modal").modal("show");
    $(".confirm-btn").click(function(){
      if(value!="Status"){
        $(".confirm-modal").modal("hide");
        window.location.href=url;
      }
    });
  });

  //email send confirmation
  $(".email-btn").click(function(){
    var url=$(this).attr("url");
    $(".modal-para").html("Do you want to send email");
    $(".confirm-modal").modal("show");
    $(".confirm-btn").click(function(){
     
        $(".confirm-modal").modal("hide");
        window.location.href=url;
      
    });
  });

  //end email send confirmation


  //delete report confirmation
  $(".delete-btn").click(function(){
    var url=$(this).attr("url");
    $(".modal-para").html("Do you want to delete report");
    $(".confirm-modal").modal("show");
    $(".confirm-btn").click(function(){
     
        $(".confirm-modal").modal("hide");
        window.location.href=url;
      
    });
  });
  //end delete report confirmation

})
</script>
@endsection
