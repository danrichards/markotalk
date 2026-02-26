(function() {
  'use strict';

  const chatFeed = document.getElementById('chat-feed');
  if (!chatFeed) return;

  const spaceSlug = chatFeed.dataset.spaceSlug || document.body.dataset.spaceSlug;
  if (!spaceSlug) return;

  const currentUserId = parseInt(chatFeed.dataset.currentUserId) || null;

  let nextCursor = null;
  let isLoadingOlder = false;
  let userScrolledUp = false;

  // Auto-scroll to bottom on load
  function scrollToBottom() {
    chatFeed.scrollTop = chatFeed.scrollHeight;
  }

  // Check if user is near bottom (within 100px)
  function isNearBottom() {
    return chatFeed.scrollHeight - chatFeed.scrollTop - chatFeed.clientHeight < 100;
  }

  // Deduplication: check if message already exists
  function messageExists(id) {
    return !!chatFeed.querySelector(`[data-message-id="${id}"]`);
  }

  // Append new message HTML
  function appendMessage(html) {
    const atBottom = isNearBottom();
    chatFeed.insertAdjacentHTML('beforeend', html);
    if (atBottom) {
      scrollToBottom();
    }
  }

  // SSE connection
  const eventSource = new EventSource(`/spaces/${spaceSlug}/stream`);

  eventSource.addEventListener('space:' + spaceSlug, (e) => {
    const event = JSON.parse(e.data);
    switch (event.type) {
      case 'message': {
        const tmp = document.createElement('div');
        tmp.innerHTML = event.html;
        const msgEl = tmp.firstElementChild;
        if (msgEl) {
          const msgId = msgEl.dataset.messageId;
          if (msgId && !messageExists(msgId)) {
            appendMessage(event.html);
            if (currentUserId && event.userId === currentUserId) {
              const csrfToken = document.querySelector('[name="_token"]')?.value ?? '';
              const newMsgEl = chatFeed.querySelector(`[data-message-id="${msgId}"]`);
              if (newMsgEl && !newMsgEl.querySelector('.message-actions')) {
                newMsgEl.insertAdjacentHTML('beforeend', `<div class="message-actions"><button class="message-action-edit" data-message-id="${msgId}" title="Edit">&#9999;</button><button class="message-action-delete" data-message-id="${msgId}" data-csrf-token="${csrfToken}" title="Delete">&#128465;</button></div>`);
              }
            }
          }
        }
        break;
      }
      case 'message_edited': {
        const msgEl = chatFeed.querySelector(`[data-message-id="${event.id}"]`);
        if (msgEl) {
          const bodyEl = msgEl.querySelector('.message-body');
          if (bodyEl) bodyEl.innerHTML = event.bodyHtml;
        }
        break;
      }
      case 'message_deleted': {
        const msgEl = chatFeed.querySelector(`[data-message-id="${event.id}"]`);
        if (msgEl) msgEl.remove();
        break;
      }
      case 'presence': {
        const onlineIds = event.onlineIds;
        document.querySelectorAll('.member-item').forEach(item => {
          const userId = parseInt(item.dataset.userId);
          const indicator = item.querySelector('.member-indicator');
          if (!indicator) return;
          if (onlineIds.includes(userId)) {
            indicator.classList.remove('is-offline');
            indicator.classList.add('is-online');
          } else {
            indicator.classList.remove('is-online');
            indicator.classList.add('is-offline');
          }
        });
        break;
      }
    }
  });

  // Load older messages on scroll to top
  chatFeed.addEventListener('scroll', async () => {
    userScrolledUp = !isNearBottom();
    if (chatFeed.scrollTop === 0 && nextCursor && !isLoadingOlder) {
      isLoadingOlder = true;
      const prevHeight = chatFeed.scrollHeight;
      try {
        const res = await fetch(`/spaces/${spaceSlug}/messages?cursor=${encodeURIComponent(nextCursor)}`);
        const data = await res.json();
        if (data.items && data.items.length > 0) {
          // Prepend older messages
          const fragment = document.createDocumentFragment();
          data.items.forEach(item => {
            if (!messageExists(item.id)) {
              const div = document.createElement('div');
              div.innerHTML = item.html || '';
              if (div.firstElementChild) fragment.prepend(div.firstElementChild);
            }
          });
          chatFeed.prepend(fragment);
          // Maintain scroll position
          chatFeed.scrollTop = chatFeed.scrollHeight - prevHeight;
        }
        nextCursor = data.links?.next || null;
      } catch (e) {
        console.error('Failed to load older messages', e);
      }
      isLoadingOlder = false;
    }
  });

  // Form submission via fetch
  const messageForm = document.querySelector('.chat-input-form');
  const messageInput = document.getElementById('message-input');
  if (messageForm && messageInput) {
    messageForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const body = messageInput.value.trim();
      if (!body) return;
      const formData = new FormData(messageForm);
      try {
        await fetch(messageForm.action, {
          method: 'POST',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        messageInput.value = '';
        messageInput.focus();
      } catch (err) {
        console.error('Failed to send message', err);
      }
    });
  }

  scrollToBottom();

  // Reaction handling
  function updateReactions(messageId, grouped) {
    const container = document.querySelector(`.message-reactions[data-message-id="${messageId}"]`);
    if (!container) return;

    // Remove existing reaction buttons (keep the add button)
    container.querySelectorAll('.message-reaction').forEach(btn => btn.remove());

    // Insert updated reaction buttons before the add button
    const addBtn = container.querySelector('.message-reaction-add');
    grouped.forEach(reaction => {
      const btn = document.createElement('button');
      btn.className = 'message-reaction' + (reaction.user_reacted ? ' message-reaction-active' : '');
      btn.dataset.emoji = reaction.emoji;
      btn.dataset.messageId = messageId;
      btn.innerHTML = `${reaction.emoji} <span class="reaction-count">${reaction.count}</span>`;
      container.insertBefore(btn, addBtn);
    });
  }

  document.addEventListener('click', async (e) => {
    // Edit message
    const editBtn = e.target.closest('.message-action-edit');
    if (editBtn) {
      const messageId = editBtn.dataset.messageId;
      const msgEl = chatFeed.querySelector(`[data-message-id="${messageId}"]`);
      if (!msgEl || msgEl.querySelector('.message-edit-textarea')) return;

      const bodyEl = msgEl.querySelector('.message-body');
      const actionsEl = msgEl.querySelector('.message-actions');
      const originalHtml = bodyEl.innerHTML;
      const originalText = bodyEl.textContent;

      const textarea = document.createElement('textarea');
      textarea.className = 'message-edit-textarea';
      textarea.value = originalText;
      textarea.rows = 3;

      const btnWrap = document.createElement('div');
      btnWrap.className = 'message-edit-buttons';
      const saveBtn = document.createElement('button');
      saveBtn.className = 'message-edit-save';
      saveBtn.textContent = 'Save';
      const cancelBtn = document.createElement('button');
      cancelBtn.className = 'message-edit-cancel';
      cancelBtn.textContent = 'Cancel';
      btnWrap.append(saveBtn, cancelBtn);

      bodyEl.innerHTML = '';
      bodyEl.append(textarea, btnWrap);
      if (actionsEl) actionsEl.style.display = 'none';
      textarea.focus();

      const finish = () => {
        bodyEl.innerHTML = originalHtml;
        if (actionsEl) actionsEl.style.display = '';
      };

      cancelBtn.addEventListener('click', finish);

      textarea.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape') finish();
        if (ev.key === 'Enter' && !ev.shiftKey) { ev.preventDefault(); saveBtn.click(); }
      });

      saveBtn.addEventListener('click', async () => {
        const newBody = textarea.value.trim();
        if (!newBody || newBody === originalText) { finish(); return; }

        const csrfToken = msgEl.querySelector('[data-csrf-token]')?.dataset.csrfToken
          || document.querySelector('[name="_token"]')?.value;
        try {
          const res = await fetch(`/messages/${messageId}`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: `body=${encodeURIComponent(newBody)}`,
          });
          if (res.ok) {
            bodyEl.innerHTML = '';
            bodyEl.textContent = newBody;
            if (actionsEl) actionsEl.style.display = '';
          } else {
            finish();
          }
        } catch (err) {
          console.error('Failed to edit message', err);
          finish();
        }
      });
      return;
    }

    // Delete message
    const deleteBtn = e.target.closest('.message-action-delete');
    if (deleteBtn) {
      if (!confirm('Delete this message?')) return;
      const messageId = deleteBtn.dataset.messageId;
      const csrfToken = deleteBtn.dataset.csrfToken || document.querySelector('[name="_token"]')?.value;
      try {
        const res = await fetch(`/messages/${messageId}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        if (res.ok) {
          const msgEl = chatFeed.querySelector(`[data-message-id="${messageId}"]`);
          if (msgEl) msgEl.remove();
        }
      } catch (err) {
        console.error('Failed to delete message', err);
      }
      return;
    }

    const reactionBtn = e.target.closest('.message-reaction, .emoji-option');
    if (reactionBtn) {
      const emoji = reactionBtn.dataset.emoji;
      const messageId = reactionBtn.dataset.messageId;
      const csrfToken = document.querySelector('[name="_token"]')?.value;

      // Close any open emoji pickers
      document.querySelectorAll('.emoji-picker').forEach(p => { p.hidden = true; });

      try {
        const res = await fetch(`/messages/${messageId}/reactions`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `emoji=${encodeURIComponent(emoji)}&_token=${csrfToken}`,
        });
        const grouped = await res.json();
        updateReactions(messageId, grouped);
      } catch (err) {
        console.error('Failed to update reaction', err);
      }
    }

    // Toggle emoji picker
    const addBtn = e.target.closest('.message-reaction-add');
    if (addBtn) {
      const msgId = addBtn.dataset.messageId;
      const picker = document.getElementById(`emoji-picker-${msgId}`);
      if (picker) picker.hidden = !picker.hidden;
    }
  });
})();
