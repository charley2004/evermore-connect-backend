(function () {
  'use strict';

  // Derive API base and params from this script's src URL
  var script = document.currentScript;
  if (!script) return;
  var src    = new URL(script.src);
  var API    = src.origin;
  var site   = src.searchParams.get('site')  || 'default';
  var theme  = src.searchParams.get('theme') || 'dark';

  var dark = theme === 'dark';
  var C = {
    bg:       dark ? '#0C1E10' : '#ffffff',
    bg2:      dark ? '#071609' : '#f9fafb',
    border:   dark ? 'rgba(201,168,76,0.12)' : '#e5e7eb',
    borderHv: dark ? 'rgba(201,168,76,0.32)' : '#C9A84C',
    text:     dark ? '#F0F7EF' : '#111111',
    muted:    dark ? '#A8C4A0' : '#6b7280',
    gold:     '#C9A84C',
    star:     '#f5a623',
  };

  // ── Inject widget styles ──────────────────────────────────────────────────
  var style = document.createElement('style');
  style.textContent = [
    '.nxr{font-family:"Plus Jakarta Sans",Inter,system-ui,sans-serif}',
    '.nxr-bar{display:flex;align-items:center;gap:10px;margin-bottom:28px;flex-wrap:wrap}',
    '.nxr-bigstar{color:'+C.star+';font-size:22px;letter-spacing:2px}',
    '.nxr-score{font-size:22px;font-weight:800;color:'+C.text+'}',
    '.nxr-count{font-size:13px;color:'+C.muted+'}',
    '.nxr-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:18px}',
    '@media(max-width:640px){.nxr-grid{grid-template-columns:1fr}}',
    '.nxr-card{background:'+C.bg+';border:1px solid '+C.border+';border-radius:14px;padding:22px;display:flex;flex-direction:column;gap:12px;transition:border-color .25s,transform .25s}',
    '.nxr-card:hover{border-color:'+C.borderHv+';transform:translateY(-2px)}',
    '.nxr-cstar{color:'+C.star+';font-size:15px;letter-spacing:1px}',
    '.nxr-body{font-size:13.5px;color:'+C.muted+';line-height:1.7;font-style:italic;flex:1;margin:0}',
    '.nxr-auth{display:flex;align-items:center;gap:10px}',
    '.nxr-av{width:36px;height:36px;border-radius:50%;background:'+C.gold+';color:#000;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}',
    '.nxr-nm{font-size:13px;font-weight:700;color:'+C.text+';display:block}',
    '.nxr-lc{font-size:11px;color:'+C.muted+';display:block;margin-top:2px}',
    '.nxr-empty,.nxr-load{color:'+C.muted+';font-size:13px;padding:8px 0}',
    '.nxr-btnwrap{margin-top:28px;text-align:center}',
    '.nxr-btn{display:inline-flex;align-items:center;gap:8px;background:transparent;border:1.5px solid '+C.borderHv+';color:'+C.gold+';padding:10px 22px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .25s}',
    '.nxr-btn:hover{background:rgba(201,168,76,.1)}',
    '.nxr-fw{margin-top:20px;background:'+C.bg2+';border:1px solid '+C.border+';border-radius:14px;padding:24px;display:none}',
    '.nxr-fw.open{display:block}',
    '.nxr-ftitle{font-size:15px;font-weight:700;color:'+C.text+';margin-bottom:16px}',
    '.nxr-field{display:flex;flex-direction:column;gap:5px;margin-bottom:13px}',
    '.nxr-lbl{font-size:11px;font-weight:700;color:'+C.muted+';text-transform:uppercase;letter-spacing:.05em}',
    '.nxr-inp{background:'+C.bg2+';border:1px solid '+C.border+';color:'+C.text+';border-radius:8px;padding:10px 13px;font-size:13px;font-family:inherit;outline:none;transition:border-color .2s;width:100%;box-sizing:border-box}',
    '.nxr-inp:focus{border-color:'+C.gold+'}',
    '.nxr-ta{resize:vertical;min-height:85px}',
    '.nxr-srow{display:flex;gap:4px}',
    '.nxr-sbtn{font-size:26px;cursor:pointer;color:'+C.border+';transition:color .15s;background:none;border:none;padding:0 2px;line-height:1}',
    '.nxr-sbtn.lit{color:'+C.star+'}',
    '.nxr-sub{background:linear-gradient(135deg,#C9A84C,#b8952e);color:#000;border:none;padding:11px 24px;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;width:100%;margin-top:6px;transition:opacity .2s}',
    '.nxr-sub:hover{opacity:.88}',
    '.nxr-sub:disabled{opacity:.45;cursor:not-allowed}',
    '.nxr-msg{font-size:13px;margin-top:10px}',
    '.nxr-msg.ok{color:#5FAF7B}',
    '.nxr-msg.er{color:#FF6B6B}',
  ].join('');
  document.head.appendChild(style);

  // ── Helpers ───────────────────────────────────────────────────────────────
  function stars(n, size) {
    return '<span class="nxr-'+(size||'c')+'star" aria-label="'+n+' out of 5">' +
      '★'.repeat(n) + '☆'.repeat(5 - n) + '</span>';
  }

  function initials(name) {
    return name.trim().split(/\s+/).slice(0, 2).map(function(w){ return w[0] || ''; }).join('').toUpperCase();
  }

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // ── Fetch ─────────────────────────────────────────────────────────────────
  function fetchReviews() {
    return fetch(API + '/api/reviews?site=' + encodeURIComponent(site))
      .then(function(r){ return r.json(); })
      .then(function(d){ return d.reviews || []; })
      .catch(function(){ return []; });
  }

  // ── Render ────────────────────────────────────────────────────────────────
  function card(r) {
    return '<div class="nxr-card">' +
      stars(r.rating) +
      '<p class="nxr-body">&ldquo;' + esc(r.body) + '&rdquo;</p>' +
      '<div class="nxr-auth">' +
        '<div class="nxr-av">' + esc(initials(r.name)) + '</div>' +
        '<div>' +
          '<span class="nxr-nm">' + esc(r.name) + '</span>' +
          '<span class="nxr-lc">' + esc(r.location) + '</span>' +
        '</div>' +
      '</div>' +
    '</div>';
  }

  function formHTML() {
    return '<div class="nxr-btnwrap">' +
        '<button class="nxr-btn" id="nxr-tog">✦ Leave a Review</button>' +
      '</div>' +
      '<div class="nxr-fw" id="nxr-fw">' +
        '<div class="nxr-ftitle">Share Your Experience</div>' +
        '<div class="nxr-field"><label class="nxr-lbl">Your Rating</label>' +
          '<div class="nxr-srow" id="nxr-srow">' +
            [1,2,3,4,5].map(function(n){
              return '<button class="nxr-sbtn" data-v="'+n+'" type="button" aria-label="'+n+' star">★</button>';
            }).join('') +
          '</div>' +
          '<input type="hidden" id="nxr-rat" value="0">' +
        '</div>' +
        '<div class="nxr-field"><label class="nxr-lbl">Full Name</label>' +
          '<input class="nxr-inp" id="nxr-name" placeholder="e.g. Adebayo Okafor" maxlength="80">' +
        '</div>' +
        '<div class="nxr-field"><label class="nxr-lbl">City / State</label>' +
          '<input class="nxr-inp" id="nxr-loc" placeholder="e.g. Lagos, Lagos State" maxlength="100">' +
        '</div>' +
        '<div class="nxr-field"><label class="nxr-lbl">Your Review</label>' +
          '<textarea class="nxr-inp nxr-ta" id="nxr-body" placeholder="Tell others about your experience…" maxlength="800"></textarea>' +
        '</div>' +
        '<button class="nxr-sub" id="nxr-sub">Submit Review</button>' +
        '<div class="nxr-msg" id="nxr-msg"></div>' +
      '</div>';
  }

  // ── Wire up ───────────────────────────────────────────────────────────────
  function wire(target) {
    var sel = 0;
    var sbtns = target.querySelectorAll('.nxr-sbtn');

    function highlight(v) {
      sbtns.forEach(function(b){ b.classList.toggle('lit', +b.dataset.v <= v); });
    }

    sbtns.forEach(function(b) {
      b.addEventListener('mouseenter', function(){ highlight(+b.dataset.v); });
      b.addEventListener('mouseleave', function(){ highlight(sel); });
      b.addEventListener('click', function(){
        sel = +b.dataset.v;
        target.querySelector('#nxr-rat').value = sel;
        highlight(sel);
      });
    });

    target.querySelector('#nxr-tog').addEventListener('click', function() {
      target.querySelector('#nxr-fw').classList.toggle('open');
    });

    target.querySelector('#nxr-sub').addEventListener('click', function() {
      var name  = target.querySelector('#nxr-name').value.trim();
      var loc   = target.querySelector('#nxr-loc').value.trim();
      var body  = target.querySelector('#nxr-body').value.trim();
      var rat   = +target.querySelector('#nxr-rat').value;
      var msg   = target.querySelector('#nxr-msg');
      var btn   = target.querySelector('#nxr-sub');

      if (!name || !body || !rat) {
        msg.className = 'nxr-msg er';
        msg.textContent = 'Please fill in your name, a rating, and your review.';
        return;
      }

      btn.disabled = true;
      btn.textContent = 'Submitting…';
      msg.textContent = '';

      fetch(API + '/api/reviews', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ site: site, name: name, location: loc, rating: rat, body: body }),
      })
      .then(function(r){ return r.json(); })
      .then(function(d) {
        if (d.success) {
          msg.className = 'nxr-msg ok';
          msg.textContent = '✓ Thank you! Your review is pending approval and will appear shortly.';
          target.querySelector('#nxr-name').value = '';
          target.querySelector('#nxr-loc').value  = '';
          target.querySelector('#nxr-body').value  = '';
          target.querySelector('#nxr-rat').value   = 0;
          sel = 0;
          highlight(0);
        } else {
          throw new Error(d.error || 'Failed');
        }
      })
      .catch(function(e) {
        msg.className = 'nxr-msg er';
        msg.textContent = e.message || 'Something went wrong. Please try again.';
      })
      .finally(function() {
        btn.disabled = false;
        btn.textContent = 'Submit Review';
      });
    });
  }

  // ── Init ──────────────────────────────────────────────────────────────────
  function init() {
    var target = document.getElementById('nx-reviews');
    if (!target) return;

    target.innerHTML = '<div class="nxr"><p class="nxr-load">Loading reviews…</p></div>';

    fetchReviews().then(function(reviews) {
      var total = reviews.length;
      var avg   = total
        ? (reviews.reduce(function(s,r){ return s + r.rating; }, 0) / total).toFixed(1)
        : null;

      var html = '<div class="nxr">';

      if (avg) {
        html += '<div class="nxr-bar">' +
          '<span class="nxr-bigstar">★★★★★</span>' +
          '<span class="nxr-score">' + avg + '</span>' +
          '<span class="nxr-count">from ' + total + ' verified distributor' + (total !== 1 ? 's' : '') + '</span>' +
        '</div>';
      }

      if (total) {
        html += '<div class="nxr-grid">' + reviews.map(card).join('') + '</div>';
      } else {
        html += '<p class="nxr-empty">Be the first to leave a review.</p>';
      }

      html += formHTML() + '</div>';
      target.innerHTML = html;
      wire(target);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
