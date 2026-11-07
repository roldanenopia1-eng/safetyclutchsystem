</main>
<footer class="foot">© <?= date('Y') ?> Safety Clutch System — ride safe, rate honestly.</footer>

<!-- Chatbot -->
<button id="bot-toggle" class="bot-toggle" title="Chat with SafeBot">💬</button>
<div id="bot" class="bot hidden">
  <div class="bot-head"><strong>SafeBot</strong><button id="bot-close" class="x">×</button></div>
  <div id="bot-log" class="bot-log"></div>
  <form id="bot-form" class="bot-form">
    <input id="bot-input" placeholder="Ask about booking, discounts, payment…" autocomplete="off">
    <button class="btn small">Send</button>
  </form>
</div>
<script src="assets/app.js"></script>
</body>
</html>
