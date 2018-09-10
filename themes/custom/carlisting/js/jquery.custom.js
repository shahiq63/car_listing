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
});