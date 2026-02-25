(function() {
  'use strict';

  const chatFeed = document.getElementById('chat-feed');
  if (!chatFeed) return;

  const spaceSlug = chatFeed.dataset.spaceSlug || document.body.dataset.spaceSlug;
  if (!spaceSlug) return;

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

  eventSource.addEventListener('message', (e) => {
    const tmp = document.createElement('div');
    tmp.innerHTML = e.data;
    const msgEl = tmp.firstElementChild;
    if (msgEl) {
      const msgId = msgEl.dataset.messageId;
      if (msgId && !messageExists(msgId)) {
        appendMessage(e.data);
      }
    }
  });

  eventSource.addEventListener('presence', (e) => {
    const onlineIds = JSON.parse(e.data);
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
    // Delete message
    const deleteBtn = e.target.closest('.message-action-delete');
    if (deleteBtn) {
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
