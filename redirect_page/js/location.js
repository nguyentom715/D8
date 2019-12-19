jQuery(document).ready(function () {
  var url = (window.location.search.split('url=')[1]);
  url = decodeURIComponent(url.replace(/\+/g, ' '));
  jQuery('#external_link')
    .attr('href', url)
    .text(url);
  jQuery('a.external_link')
    .attr('href', url);
});