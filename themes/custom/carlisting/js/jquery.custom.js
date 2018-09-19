jQuery(function($) {
  $(".inline-element").parent("div").addClass("inline-elements");
  $('form').find('.form-item-field-price-value-min').addClass("inline-elements");
  $('form').find('.form-item-field-price-value-max').addClass("inline-elements");
  $('form').find('.form-item-field-year-value-min').addClass("inline-elements");
  $('form').find('.form-item-field-year-value-max').addClass("inline-elements");
  $(".views-field-field-pro").on("click", function(e){
    var col = $(e.target).parent().parent().parent().attr("class");
    var row= $(e.target).parent().parent().parent().parent().attr("class");
    var res = col.split(" ");
    var res1 = row.split(" ");
    var Description= $('.'+res1[2]).find('.'+res[1]).find(".views-field-field-description").find(".field-content").text();
    $("#dialog-message").text(Description);
    $("#dialog-message" ).dialog({
      title:"Information",
      buttons: {
        Ok: function() {
          $(this).dialog( "close" );
        }
      }
    });
  });
  $('.views-field-field-pro').hover(function() {
    $(this).css('cursor','pointer');
  });
  $('.navbar-nav').find('.dropdown').hover(function(){
    $('.dropdown').addClass('open');
  },function(){
    $('.dropdown').removeClass('open');
  });
  $('#comment-form').parent().parent().addClass('col-sm-12');
  $('.js-comment').parent().addClass('col-sm-12');
  $("#comment-form").hide();
  $('#comment-form #edit-submit').text("Place Comment");
  $('.show_comment').click(function(){
    $(this).text(function(i, v){
      return v === 'Add Comment' ? 'Hide Comment' : 'Add Comment'
    });
    $("#comment-form").toggle();
  });
});
var left;
var right;
var check=0;
jQuery( document ).ajaxStart(function($) {
  jQuery('#block-views-block-compare-right-side-car-block-1').find('.con_present').hide();
  jQuery('#block-views-block-duplicate-of-compare-left-side-car-block-1').find('.con_present').hide();
  jQuery('#views-exposed-form-compare-right-side-car-block-1 .js-form-submit').click(function(){
     left=jQuery('#views-exposed-form-compare-right-side-car-block-1').find("input[name=title]").val();
     right=jQuery('#views-exposed-form-duplicate-of-compare-left-side-car-block-1').find("input[name=title]").val();
    if(left==right) {
      check=1;
    }else {
      check=0;
    }
  });
  jQuery('#views-exposed-form-duplicate-of-compare-left-side-car-block-1 .js-form-submit').click(function(){
     left=jQuery('#views-exposed-form-compare-right-side-car-block-1').find("input[name=title]").val();
     right=jQuery('#views-exposed-form-duplicate-of-compare-left-side-car-block-1').find("input[name=title]").val();
    if(right==left) {
      check=2;
    }else {
      check=0;
   }
 });
});
jQuery( document ).ajaxComplete(function($) {
  if(check==1) {
    jQuery('#views-exposed-form-compare-right-side-car-block-1').find("input[name=title]").val('');
    jQuery('#block-views-block-compare-right-side-car-block-1').find('.view-content').hide();
    jQuery('#block-views-block-compare-right-side-car-block-1').append('<div class="con_present"></div>');
  }
  if(check==2) {
    jQuery('#views-exposed-form-duplicate-of-compare-left-side-car-block-1').find("input[name=title]").val('');
    jQuery('#block-views-block-duplicate-of-compare-left-side-car-block-1').find('.view-content').hide();
    jQuery('#block-views-block-duplicate-of-compare-left-side-car-block-1').append('<div class="con_present"></div>');
  }
});