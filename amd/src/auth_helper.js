define(['jquery', 'core/notification'], function($, Notification) {
    
    var firebaseConfig = null;
    var auth = null;
    var idToken = null;

    /**
     * Initialize Firebase and Auth
     */
    async function init(config) {
        firebaseConfig = config;
        console.log('Cuppa AI Auth Helper: Initializing...');

        // Dynamic load Firebase from CDN
        if (typeof window.firebase === 'undefined') {
            await import("https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js").then(async (m) => {
                const { initializeApp } = m;
                const { getAuth, GoogleAuthProvider, signInWithPopup, onAuthStateChanged } = await import("https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js");
                
                const app = initializeApp(firebaseConfig);
                auth = getAuth(app);
                
                onAuthStateChanged(auth, async (user) => {
                    if (user) {
                        idToken = await user.getIdToken();
                        $('#auth-status').html('✅ Logged in as ' + user.email);
                        $('#btn-cuppa-login').hide();
                        $('#aitutorial-main-content').fadeIn();
                        
                        // Set token in hidden form field
                        $('input[name="firebase_token"]').val(idToken);
                        
                        checkSubscription(user);
                    } else {
                        $('#auth-status').text('🔑 Authentication Required');
                        $('#btn-cuppa-login').show();
                        $('#aitutorial-main-content').hide();
                    }
                });
            });
        }

        $('#btn-cuppa-login').click(function() {
            handleLogin();
        });
    }

    async function handleLogin() {
        const { GoogleAuthProvider, signInWithPopup } = await import("https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js");
        const provider = new GoogleAuthProvider();
        try {
            await signInWithPopup(auth, provider);
        } catch (error) {
            Notification.alert('Login Error', error.message);
        }
    }

    function checkSubscription(user) {
        // Here you would normally call your backend to check Firestore is_pro
        // For now, we'll just display a placeholder
        $('#subscription-status').html('Plan: <strong>Pro</strong> (Synced from Cuppa AI)');
    }

    return {
        init: init
    };
});
