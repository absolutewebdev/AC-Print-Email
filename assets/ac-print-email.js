document.addEventListener('DOMContentLoaded', function () {
  function enc(s){ return encodeURIComponent(s || ''); }

  function getData(root){
    var subject = root.getAttribute('data-email-subject') || document.title;
    var url     = root.getAttribute('data-email-body') || location.href;

    var body =
      "This message was sent from Apostles-Creed.org for you to review:\n\n" +
      subject + ",\n\n" +
      url;

    return { subject: subject, body: body, url: url };
  }

  function buildLinks(root){
    var menu = root.querySelector('.acpe-menu');
    if (!menu) return;

    var d = getData(root);

    var gmail   = "https://mail.google.com/mail/?view=cm&fs=1&tf=1&su=" + enc(d.subject) + "&body=" + enc(d.body);
    var yahoo   = "https://compose.mail.yahoo.com/?subj=" + enc(d.subject) + "&body=" + enc(d.body);
    var outlook = "https://outlook.live.com/mail/0/deeplink/compose?subject=" + enc(d.subject) + "&body=" + enc(d.body);
    var aol     = "https://mail.aol.com/webmail-std/en-us/composemessage?subject=" + enc(d.subject) + "&body=" + enc(d.body);

    function setHref(sel, href){
      var a = root.querySelector(sel);
      if (a) a.href = href;
    }

    setHref('.acpe-gmail', gmail);
    setHref('.acpe-yahoo', yahoo);
    setHref('.acpe-outlook', outlook);
    setHref('.acpe-aol', aol);

    var mailto = root.querySelector('.acpe-mailto');
    if (mailto) mailto.href = "mailto:?subject=" + enc(d.subject) + "&body=" + enc(d.body);

    var copy = root.querySelector('.acpe-copy');
    if (copy) {
      copy.onclick = function(e){
        e.preventDefault();

        var labelEl = copy.querySelector('.acpe-label');
        var prev = labelEl ? labelEl.textContent : copy.textContent;

        function setTemp(text){
          if (labelEl) labelEl.textContent = text;
          else copy.textContent = text;
          setTimeout(function(){
            if (labelEl) labelEl.textContent = prev;
            else copy.textContent = prev;
          }, 1200);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(d.url).then(function(){
            setTemp("Copied");
          }).catch(function(){
            prompt('Copy this link:', d.url);
          });
        } else {
          prompt('Copy this link:', d.url);
        }
      };
    }
  }

  function closeAll(exceptRoot){
    var toolbars = document.querySelectorAll('.acpe-toolbar');
    for (var i=0; i<toolbars.length; i++){
      var root = toolbars[i];
      if (exceptRoot && root === exceptRoot) continue;
      var menu = root.querySelector('.acpe-menu');
      var toggle = root.querySelector('.acpe-email-toggle');
      if (menu) menu.hidden = true;
      if (toggle) toggle.setAttribute('aria-expanded','false');
    }
  }

  // ===== Init toolbars =====
  var roots = document.querySelectorAll('.acpe-toolbar[data-acpe="1"]');
  for (var r=0; r<roots.length; r++){
    (function(root){
      var printBtn = root.querySelector('.acpe-print');
      var toggle = root.querySelector('.acpe-email-toggle');
      var menu = root.querySelector('.acpe-menu');

      if (printBtn) {
        printBtn.addEventListener('click', function(e){
          e.preventDefault();
          window.print();
        });
      }

      if (toggle && menu) {
        buildLinks(root);

        toggle.addEventListener('click', function(e){
          e.preventDefault();
          var isOpen = !menu.hidden;
          closeAll(root);
          buildLinks(root);
          menu.hidden = isOpen;
          toggle.setAttribute('aria-expanded', String(!menu.hidden));
        });
      }
    })(roots[r]);
  }

  // ===== Move archive toolbar from hidden footer holder into the term header area =====
  (function(){
    var holder = document.getElementById('acpe-archive-toolbar-holder');
    if (!holder) return;

    var tb = holder.querySelector('.acpe-toolbar');
    if (!tb) return;

    var target =
      document.querySelector('.bu-term-after-header') ||
      document.querySelector('.bu-term-header') ||
      document.querySelector('#main') ||
      document.querySelector('#content');

    if (target) {
      if (target.classList.contains('bu-term-after-header')) {
        target.appendChild(tb);
      } else if (target.classList.contains('bu-term-header')) {
        target.parentNode.insertBefore(tb, target.nextSibling);
      } else {
        target.insertBefore(tb, target.firstChild);
      }
      holder.parentNode.removeChild(holder);
    }
  })();

  // Close on outside click / Escape
  document.addEventListener('click', function(e){
    var root = e.target.closest && e.target.closest('.acpe-toolbar');
    if (!root) closeAll();
  });

  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') closeAll();
  });
});
