
$(document).keypress(function (event) {
  var elem, e = event ? event : event;
  if (event.srcElement)
    elem = event.srcElement;
  else if (event.target)
    elem = event.target;
  var elementName = elem.tagName.toUpperCase();
  if (elementName === 'TEXTAREA' && $('#profile-jot-text').is(event.target)) {
    
    if (event.ctrlKey && event.key === "/") {
      alert('b: bold\ni: italics\nu: underline\nctrl+\': quote\nctrl+;: code\nctrl+1: preview post\nctrl+2: open ACL dialog');
      return;
    }
    if (event.ctrlKey && event.key === "b") {
      $('#main-editor-bold').click();
      return;
    }     
    if (event.ctrlKey && event.key === "i") {
      $('#main-editor-italic').click();
      return;
    }     
    if (event.ctrlKey && event.key === "u") {
      $('#main-editor-underline').click();
      return;
    } 
    
    if (event.ctrlKey && event.key === "'") {
        $('#main-editor-quote').click();
      return;
    } 
    if (event.ctrlKey && event.key === ";") {
      $('#main-editor-code').click();
      return;
    }
    if (event.ctrlKey && event.key === "1") {
      preview_post();
      return;
    }     
    if (event.ctrlKey && event.key === "2") {
      $('#dbtn-acl').click();
      return;
    }     
  }
  if (!(elementName === 'INPUT' || elementName === 'TEXTAREA')) {
    if (event.shiftKey && event.key === "?") {
      contextualHelp();
      return;
    }
    if (event.key === "n" || event.keyCode === 39) {
      //window.console.log("you pressed " + event.key);
      var currentPost = null;
      var nextPost = null;
      $('.toplevel_item').each(function (idx) {
        if (currentPost !== null && nextPost === null) {
          nextPost = $(this);
        } else if (currentPost === null && $(this).isOnScreen()) {
          //window.console.log('Thread ID: ' + $(this).attr('id'));
          currentPost = $(this);
        }
      });
      if (nextPost !== null) {
        $('html,body').animate({scrollTop: nextPost.offset().top - $('#avatar').height()}, 'medium');
      }
      return;
    }
    if (event.key === "p" || event.keyCode === 37) {
      //window.console.log("you pressed " + event.key);
      var currentPost = null;
      var prevPost = null;
      $('.toplevel_item').each(function (idx) {
        if (currentPost === null && $(this).isOnScreen()) {
          //window.console.log('Thread ID: ' + $(this).attr('id'));
          currentPost = $(this);
        }
        if (currentPost === null) {
          prevPost = $(this);
        }
      });
      if (prevPost !== null) {
        $('html,body').animate({scrollTop: prevPost.offset().top - $('#avatar').height()}, 'medium');
      }
      return;
    }
    if (event.key === "t") {
      $('html,body').animate({scrollTop: 0}, 'medium');
      return;
    }
    if (event.key === "k") {
      alert('p, left arrow: previous post\nn, right arrow: next post\ns: expand/collapse comments\nt: scroll to top of page\n?: open context help');
      return;
    }
    if (event.key === "s") {
      var idNum = null;
      $('.toplevel_item').each(function (idx) {
        if (idNum === null && $(this).isOnScreen()) {
          //window.console.log('Thread ID: ' + $(this).attr('id'));
          idNum = parseInt($(this).attr('id').split('-').pop()) + 1;
          //window.console.log('Extracted Comment ID: ' + idNum);
        }
      });
      if (idNum !== null) {
        showHideComments(idNum);
      }
      return;
    }
  }
});

$.fn.isOnScreen = function () {
  var element = this.get(0);
  var bounds = element.getBoundingClientRect();
  return bounds.top < window.innerHeight && bounds.top > $('#avatar').height() && bounds.bottom > 0;
}