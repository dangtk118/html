/********Js For gallerymedia************/

 document.observe("dom:loaded", function() {
  $('shipping_express_price').hide();
  $('shipping_express_fixed_price').hide();    
  if($('shipping_express').value == '1'){
    $('shipping_express_price').show();
    if($('shipping_express_price').value == '1'){
    $('shipping_express_fixed_price').show();
  } 
  }else {
    $('shipping_express_price').hide();
    $('shipping_express_fixed_price').hide();
  } 
  
  if($('shipping_sameday').value == '1'){
     $('shipping_sameday_fixed_price').show();
  }else {
    $('shipping_sameday_fixed_price').hide();
  }
}); 

function changeSelect(){  
  if($('shipping_express').value == '1'){
    $('shipping_express_price').show();
  }else{
   $('shipping_express_price').hide();
   $('shipping_express_fixed_price').hide();
  }
}
function changeSelectOptionPrice() {
  if($('shipping_express_price').value == '1'){
     $('shipping_express_fixed_price').show();
  }else {
    $('shipping_express_fixed_price').hide();
  }
}
function changeSelectsamedayOption() {
  if($('shipping_sameday').value == '1'){
     $('shipping_sameday_fixed_price').show();
  }else {
    $('shipping_sameday_fixed_price').hide();
  }
}
