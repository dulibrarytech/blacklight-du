// This is a manifest file that'll be compiled into application.js, which will include all the files
// listed below.
//
// Any JavaScript/Coffee file within this directory, lib/assets/javascripts, vendor/assets/javascripts,
// or any plugin's vendor/assets/javascripts directory can be referenced here using a relative path.
//
// It's not advisable to add code directly here, but if you do, it'll appear at the bottom of the
// compiled file. JavaScript code in this file should be added after the last require_* statement.
//
// Read Sprockets README (https://github.com/rails/sprockets#sprockets-directives) for details
// about supported directives.
//
//= require jquery
//= require jquery_ujs
//= require turbolinks//
// Required by Blacklight
//= require blacklight/blacklight

//= require_tree .

/*
 * DU: Convert  show link value to link
 */
$( document ).ready(function() {
  var x = document.getElementsByClassName("blacklight-links");
  var linkStr = x[1].innerHTML;
  x[1].innerHTML = '<a style="cursor: pointer;" class="" href="' + linkStr + '">' + linkStr + '</a>';
});
