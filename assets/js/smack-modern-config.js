document.addEventListener('DOMContentLoaded', () => {
    // Tab switching
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const target = btn.getAttribute('data-tab');

            // Update buttons
            tabBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Update panes
            tabPanes.forEach(p => p.classList.remove('active'));
            const targetPane = document.getElementById(target);
            if (targetPane) targetPane.classList.add('active');
        });
    });

    // Provider switching
    const switchers = [
        { id: 'provider-selector', class: '.provider-fields' },
        { id: 'email-provider-selector', class: '.email-provider-fields' },
        { id: 'sms-provider-selector', class: '.sms-provider-fields' }
    ];

    switchers.forEach(s => {
        const el = document.getElementById(s.id);
        if (el) {
            el.addEventListener('change', () => {
                const val = el.value;
                document.querySelectorAll(s.class).forEach(f => f.style.display = 'none');

                // For main provider and email provider, fields are id='val-fields'
                // For SMS, it's 'sms-val-fields'
                let targetId = val + '-fields';
                if (s.id === 'sms-provider-selector') targetId = 'sms-' + val + '-fields';

                const target = document.getElementById(targetId);
                if (target) target.style.display = 'block';
            });
            // Init
            el.dispatchEvent(new Event('change'));
        }
    });

    // Help Modal logic
    const helpData = {
        twilio: "<h4>Twilio Setup</h4><div class='help-step'><span class='step-num'>1</span>Log in to Twilio Console.</div><div class='help-step'><span class='step-num'>2</span>Copy Account SID and Auth Token.</div><div class='help-step'><span class='step-num'>3</span>Use your active Twilio phone number.</div>",
        infobip: "<h4>Infobip Setup</h4><div class='help-step'><span class='step-num'>1</span>Go to Infobip Portal.</div><div class='help-step'><span class='step-num'>2</span>Create an API Key from Settings.</div><div class='help-step'><span class='step-num'>3</span>Find your unique Base URL (e.g. https://xxx.api.infobip.com).</div>",
        gupshup: "<h4>Gupshup Setup</h4><div class='help-step'><span class='step-num'>1</span>Log in to Gupshup Dashboard.</div><div class='help-step'><span class='step-num'>2</span>Copy API Key from App Settings.</div><div class='help-step'><span class='step-num'>3</span>Enter your registered Bot Name.</div>",
        sendgrid: "<h4>SendGrid Setup</h4><div class='help-step'><span class='step-num'>1</span>Go to SendGrid Settings > API Keys.</div><div class='help-step'><span class='step-num'>2</span>Create a key with 'Mail Send' permissions.</div><div class='help-step'><span class='step-num'>3</span>Ensure your 'From Email' is verified.</div>",
        mailtrap: "<h4>Mailtrap Setup</h4><div class='help-step'><span class='step-num'>1</span>Go to Mailtrap Settings > API Tokens.</div><div class='help-step'><span class='step-num'>2</span>Copy your API Token.</div><div class='help-step'><span class='step-num'>3</span>Use your Mailtrap registered sender email.</div>",
        brevo: "<h4>Brevo Setup</h4><div class='help-step'><span class='step-num'>1</span>Go to SMTP & API section in Brevo.</div><div class='help-step'><span class='step-num'>2</span>Create a new v3 API Key.</div><div class='help-step'><span class='step-num'>3</span>Use your verified sender email address.</div>",
        sms: "<h4>Twilio SMS Setup</h4><div class='help-step'><span class='step-num'>1</span>Log in to your Twilio Console.</div><div class='help-step'><span class='step-num'>2</span>Go to Phone Numbers &gt; Manage &gt; Active Numbers.</div><div class='help-step'><span class='step-num'>3</span>Copy your SMS-enabled number and paste it in \"Twilio SMS Number\".</div><div class='help-step'><span class='step-num'>4</span>Ensure it includes the + prefix.</div><p style='margin-top:12px;font-size:13px;color:#64748b;'>Note: Trial accounts are limited to 160 characters for SMS.</p>",
        admin: "<h4>Admin Settings</h4><p>Enter your primary WhatsApp number with country code (e.g. +123456789). This number will receive all administrative alerts.</p>",
        templates: "<h4>Message Templates</h4><p>Customize your notifications here. Use <b>[Order ID]</b> as a placeholder. Templates are applied to both WhatsApp and Email notifications.</p>",
        toggles: "<h4>Notification Toggles</h4><p>Switch notifications ON/OFF for specific events. You can independently control WhatsApp and Email channels.</p>"
    };

    const modal = document.getElementById('help-modal');
    const helpTriggers = document.querySelectorAll('.help-trigger');
    const closeBtn = document.querySelector('.close-modal');

    helpTriggers.forEach(t => {
        t.addEventListener('click', () => {
            const type = t.getAttribute('data-help');
            let content = "";
            let title = "Configuration Help";

            if (type === 'api') {
                const provider = document.getElementById('provider-selector').value;
                content = helpData[provider] || "No instructions available.";
                title = "API Configuration Help";
            } else if (type === 'email') {
                const provider = document.getElementById('email-provider-selector').value;
                content = helpData[provider] || "No instructions available.";
                title = "Email Configuration Help";
            } else if (type === 'sms') {
                content = helpData['sms'] || "No instructions available.";
                title = "SMS Configuration Help";
            } else {
                content = helpData[type] || "No instructions available.";
                title = type.charAt(0).toUpperCase() + type.slice(1) + " Help";
            }

            document.getElementById('help-title').innerText = title;
            document.getElementById('help-content').innerHTML = content;
            modal.style.display = 'flex';
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', () => modal.style.display = 'none');
    }

    window.onclick = (event) => {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    };
});
