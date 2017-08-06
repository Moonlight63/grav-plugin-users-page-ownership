// JavaScript Document

(function ($) {
    
    $(window).load(function(){
        var pageusefield = $("input.realpageuse");
        var fakepageusefield = $("select.fakepageuse");
        pageusefield.closest("div.block-text").addClass("hidden");
        pageusefield.closest("div.form-field").addClass("hidden");
        fakepageusefield.prop('disabled', true);
        fakepageusefield.change(function(){
            pageusefield.val(fakepageusefield.val());
            
            if(fakepageusefield.val() === null){
                fakepageusefield.val("nopagetypes");
                pageusefield.val("nopagetypes");
            }
        });
    });
    
})(jQuery);