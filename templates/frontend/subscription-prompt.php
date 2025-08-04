<div id="pk-subscribe-modal-wrapper" class="is-hidden"></div>

<div id="pk-subscribe-bell-wrapper" style="display: none;" class="fixed left-0 <?php if (is_user_logged_in()) {
    echo 'bell-float-btn-login';
} else {
    echo 'bell-float-btn-guest';
} ?>"></div>

<div id="pk-confirmation-modal" class="pk-instruction-modal"></div>

<div id="pk-unblock-instructions" class="pk-instruction-modal"></div>
