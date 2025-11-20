// Sarap Local Demo App — localStorage-based
(function(){
  const STORAGE_KEY = 'sarap_posts_v1';
  const VENDOR_KEY = 'sarap_vendor_name';
  const DEFAULT_IMAGE = 'images/sample.jpg';

  function loadPosts(){
    try{ return JSON.parse(localStorage.getItem(STORAGE_KEY)) || seedIfEmpty(); }
    catch(e){ return seedIfEmpty(); }
  }
  function savePosts(posts){ localStorage.setItem(STORAGE_KEY, JSON.stringify(posts)); }
  function getVendorName(){ return localStorage.getItem(VENDOR_KEY) || ''; }
  function setVendorName(name){ localStorage.setItem(VENDOR_KEY, name); }

  function seedIfEmpty(){
    const existing = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
    if(existing.length) return existing;
    const sample=[
      {id:uid(), foodName:'Chicken Adobo', vendorName:'Aling Maria', price:120, location:'Brgy. 1', available:true, image:DEFAULT_IMAGE, comments:[], messages:[]},
      {id:uid(), foodName:'Sinigang na Baboy', vendorName:'Mang Juan', price:150, location:'Brgy. 3', available:true, image:DEFAULT_IMAGE, comments:[{author:'Customer', text:'Masarap ba ito?', date:ts()}], messages:[]},
      {id:uid(), foodName:'Kakanin Platter', vendorName:'Lola Berta', price:50, location:'Brgy. 2', available:false, image:DEFAULT_IMAGE, comments:[], messages:[]}
    ];
    savePosts(sample);
    return sample;
  }

  function uid(){ return 'p_' + Math.random().toString(36).slice(2) + Date.now().toString(36); }
  function ts(){ return new Date().toLocaleString(); }

  function el(html){
    const t = document.createElement('template');
    t.innerHTML = html.trim();
    return t.content.firstChild;
  }

  function toDataURL(file){
    return new Promise((resolve)=>{
      if(!file){ resolve(null); return; }
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result);
      reader.readAsDataURL(file);
    });
  }

  // ------- CUSTOMER FEED -------
  function renderCustomerFeed(){
    const feed = document.getElementById('feed');
    if(!feed) return;
    let posts = loadPosts();

    const search = document.getElementById('search');
    const availabilityFilter = document.getElementById('availabilityFilter');

    function applyFilters(){
      let q = (search?.value || '').toLowerCase();
      let f = availabilityFilter?.value || 'all';
      let list = posts.filter(p => 
        (f === 'all' || (f==='available' && p.available) || (f==='unavailable' && !p.available)) &&
        (p.foodName.toLowerCase().includes(q) || p.vendorName.toLowerCase().includes(q))
      );
      draw(list);
    }

    function draw(list){
      feed.innerHTML='';
      list.forEach((p, i)=>{
        const card = el(`
          <article class="post-card">
            <div class="post-header">
              <img class="avatar" src="${DEFAULT_IMAGE}" alt="avatar"/>
              <div>
                <strong>${p.vendorName}</strong>
                <div class="post-meta">${p.location} · ₱${p.price}
                  ${p.available ? '<span class="badge ok">Available</span>' : '<span class="badge no">Not available</span>'}
                </div>
              </div>
            </div>
            <img class="post-image" src="${p.image || DEFAULT_IMAGE}" alt="${p.foodName}"/>
            <h3>${p.foodName}</h3>
            <div class="actions">
              <button data-action="comment">💬 Comment</button>
              <button data-action="message">✉ Message Vendor</button>
            </div>
            <div class="comment-box">
              <input type="text" placeholder="Write a comment..." />
              <button data-action="add-comment">Post</button>
            </div>
            <div class="comment-list"></div>
          </article>
        `);
        // render comments
        const listEl = card.querySelector('.comment-list');
        p.comments.forEach(c => {
          listEl.appendChild(el(`<div class="comment-item"><strong>${c.author}</strong>: ${c.text} <span class="muted small">(${c.date})</span></div>`));
        });
        // actions
        card.querySelector('[data-action="comment"]').addEventListener('click', ()=>{
          const text = prompt('Your comment:');
          if(text){
            p.comments.push({author:'Customer', text, date:ts()});
            savePosts(posts);
            applyFilters();
          }
        });
        card.querySelector('[data-action="message"]').addEventListener('click', ()=>{
          const msg = prompt('Message to vendor:');
          if(msg){
            p.messages.push({from:'Customer', text:msg, date:ts()});
            savePosts(posts);
            alert('Message sent to vendor!');
          }
        });
        card.querySelector('[data-action="add-comment"]').addEventListener('click', (e)=>{
          const input = e.target.parentElement.querySelector('input');
          const text = input.value.trim();
          if(text){
            p.comments.push({author:'Customer', text, date:ts()});
            input.value='';
            savePosts(posts);
            applyFilters();
          }
        });
        feed.appendChild(card);
      });
      if(!list.length){
        feed.appendChild(el('<p class="muted">No posts match your filters.</p>'));
      }
    }

    search?.addEventListener('input', applyFilters);
    availabilityFilter?.addEventListener('change', applyFilters);
    applyFilters();
  }

  // ------- VENDOR DASHBOARD -------
  function renderVendorDashboard(){
    const nameInput = document.getElementById('vendorProfileName');
    const saveBtn = document.getElementById('saveVendorProfile');
    const form = document.getElementById('vendorForm');
    const list = document.getElementById('vendorPosts');
    const posts = loadPosts();
    nameInput.value = getVendorName();

    saveBtn.addEventListener('click', ()=>{
      const n = nameInput.value.trim();
      if(!n) return alert('Please enter your vendor name.');
      setVendorName(n);
      draw();
    });

    form.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const vendorName = getVendorName().trim();
      if(!vendorName){
        alert('Set your vendor name first (top of the page).');
        return;
      }
      const foodName = document.getElementById('foodName').value.trim();
      const price = parseFloat(document.getElementById('price').value);
      const location = document.getElementById('location').value.trim();
      const available = document.getElementById('availability').value === 'true';
      const file = document.getElementById('image').files[0];
      const imageDataUrl = (await toDataURL(file)) || DEFAULT_IMAGE;
      const newPost = { id:uid(), foodName, vendorName, price, location, available, image:imageDataUrl, comments:[], messages:[] };
      const all = loadPosts();
      all.unshift(newPost);
      savePosts(all);
      form.reset();
      draw();
      alert('Food posted!');
    });

    function draw(){
      const all = loadPosts();
      const me = getVendorName().toLowerCase();
      list.innerHTML='';
      const mine = all.filter(p => p.vendorName.toLowerCase() === me);
      if(!mine.length){
        list.appendChild(el('<p class="muted">No posts yet. Create one above.</p>'));
        return;
      }
      mine.forEach(p => {
        const card = el(`
          <article class="post-card">
            <div class="post-header">
              <img class="avatar" src="${DEFAULT_IMAGE}"/>
              <div>
                <strong>${p.vendorName}</strong>
                <div class="post-meta">${p.location} · ₱${p.price}
                  ${p.available ? '<span class="badge ok">Available</span>' : '<span class="badge no">Not available</span>'}
                </div>
              </div>
            </div>
            <img class="post-image" src="${p.image || DEFAULT_IMAGE}" alt="${p.foodName}"/>
            <h3>${p.foodName}</h3>
            <div class="actions">
              <button data-action="toggle">Toggle Availability</button>
              <button data-action="edit">✏ Edit</button>
              <button data-action="delete">🗑 Delete</button>
              <button data-action="reply">✉ Reply to Messages</button>
            </div>
            <h4>Comments</h4>
            <div class="comment-list">
              ${p.comments.map(c => `<div class="comment-item"><strong>${c.author}</strong>: ${c.text} <span class="muted small">(${c.date})</span></div>`).join('') || '<p class="muted">No comments yet.</p>'}
            </div>
            <h4>Messages</h4>
            <div class="comment-list">
              ${p.messages.map(m => `<div class="comment-item"><strong>${m.from}</strong>: ${m.text} <span class="muted small">(${m.date})</span></div>`).join('') || '<p class="muted">No messages yet.</p>'}
            </div>
          </article>
        `);
        // actions
        card.querySelector('[data-action="toggle"]').addEventListener('click', ()=>{
          const all2 = loadPosts();
          const idx = all2.findIndex(x=>x.id===p.id);
          all2[idx].available = !all2[idx].available;
          savePosts(all2); draw();
        });
        card.querySelector('[data-action="edit"]').addEventListener('click', ()=>{
          const all2 = loadPosts();
          const idx = all2.findIndex(x=>x.id===p.id);
          const cur = all2[idx];
          const name = prompt('Food name:', cur.foodName) ?? cur.foodName;
          const price = parseFloat(prompt('Price (₱):', cur.price) ?? cur.price);
          const loc = prompt('Location:', cur.location) ?? cur.location;
          all2[idx].foodName = name;
          all2[idx].price = isNaN(price) ? cur.price : price;
          all2[idx].location = loc;
          savePosts(all2); draw();
        });
        card.querySelector('[data-action="delete"]').addEventListener('click', ()=>{
          if(!confirm('Delete this post?')) return;
          const all2 = loadPosts().filter(x=>x.id!==p.id);
          savePosts(all2); draw();
        });
        card.querySelector('[data-action="reply"]').addEventListener('click', ()=>{
          const reply = prompt('Type your reply to the latest message:');
          if(!reply) return;
          const all2 = loadPosts();
          const idx = all2.findIndex(x=>x.id===p.id);
          all2[idx].messages.push({from: getVendorName() || 'Vendor', text: reply, date: ts()});
          savePosts(all2); draw();
          alert('Reply sent (stored in post).');
        });
        list.appendChild(card);
      });
    }
    draw();
  }

  window.SarapLocal = { renderCustomerFeed, renderVendorDashboard };
})();