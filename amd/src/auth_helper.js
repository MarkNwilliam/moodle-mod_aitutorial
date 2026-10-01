// Cuppa AI - Firebase Auth Helper for Moodle
// Sign In with Google, or Sign Up / Sign In with email + password.
// Mirrors the Google Doc extension's auth flow.

define(['jquery', 'core/notification'], function($, Notification) {

    var auth = null;
    var signInWithEmailAndPassword = null;
    var activeAuthMode = 'signin'; // 'signin' or 'signup'
    var BACKEND_URL = 'https://cuppai.top';

    function showAuthError(msg) {
        $('#auth-status').html('<span style="color:#d9534f;">' + msg + '</span>');
    }

    function setAuthBusy(busy) {
        $('#btn-cuppa-auth-submit').prop('disabled', busy);
        $('#btn-cuppa-login').prop('disabled', busy);
    }

    function switchAuthMode(mode) {
        activeAuthMode = mode;
        $('#tab-signin').toggleClass('active', mode === 'signin');
        $('#tab-signup').toggleClass('active', mode === 'signup');
        $('#btn-cuppa-auth-submit').text(mode === 'signin' ? 'Sign In' : 'Sign Up');
        $('#cuppa-auth-password').attr('autocomplete', mode === 'signin' ? 'current-password' : 'new-password');
    }

    function handleEmailAuthSubmit() {
        var email = $('#cuppa-auth-email').val().trim();
        var password = $('#cuppa-auth-password').val();

        if (!email || !password) {
            showAuthError('Enter your email and password.');
            return;
        }
        if (activeAuthMode === 'signup' && password.length < 6) {
            showAuthError('Password must be at least 6 characters.');
            return;
        }

        if (activeAuthMode === 'signin') {
            setAuthBusy(true);
            $('#auth-status').html('Signing in...');
            signInWithEmailAndPassword(auth, email, password)
                .then(function() {
                    // Handled globally by onAuthStateChanged.
                })
                .catch(function(error) {
                    setAuthBusy(false);
                    showAuthError(error.message);
                });
            return;
        }

        // Sign Up: create account server-side (backend also seeds the Firestore
        // profile + starter credits), then sign in client-side.
        setAuthBusy(true);
        $('#auth-status').html('Creating your account...');
        fetch(BACKEND_URL + '/api/signup', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({email: email, password: password})
        })
            .then(function(resp) {
                return resp.json().then(function(data) {
                    return {resp: resp, data: data};
                }).catch(function() {
                    return {resp: resp, data: {}};
                });
            })
            .then(function(result) {
                if (result.resp.ok) {
                    return signInWithEmailAndPassword(auth, email, password);
                }
                var msg = result.data && result.data.detail;
                msg = typeof msg === 'string' ? msg : JSON.stringify(msg);
                if (/exists|already|taken/i.test(msg || '')) {
                    // Account already exists -> just sign in.
                    return signInWithEmailAndPassword(auth, email, password);
                }
                throw new Error(msg || ('Signup failed (HTTP ' + result.resp.status + ')'));
            })
            .then(function() {
                // Handled globally by onAuthStateChanged.
            })
            .catch(function(error) {
                setAuthBusy(false);
                showAuthError(error && error.message ? error.message : 'Signup failed. Please try again.');
            });
    }

    /**
     * Initialize Firebase and auth state.
     * Called from view.php with the Firebase config.
     */
    function init(config) {
        console.log('Cuppa AI: Auth helper initializing...');

        // Backend URL comes from the plugin setting (view.php passes it in).
        // Fall back to the default host when it is not supplied.
        if (config && config.apiBaseUrl) {
            BACKEND_URL = String(config.apiBaseUrl).replace(/\/+$/, '');
        }

        if (typeof window.firebase === 'undefined') {
            try {
                console.log('Cuppa AI: Attempting to fetch Firebase App script...');
                // Use Promises (ES5 compatible) instead of async/await so Moodle's minifier doesn't crash
                import("https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js").then(function(appModule) {
                    console.log('Cuppa AI: Firebase App script loaded successfully.');
                    var initializeApp = appModule.initializeApp;

                    console.log('Cuppa AI: Attempting to fetch Firebase Auth script...');
                    import("https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js").then(function(authModule) {
                        console.log('Cuppa AI: Firebase Auth script loaded successfully.');
                        var getAuth = authModule.getAuth;
                        var GoogleAuthProvider = authModule.GoogleAuthProvider;
                        var signInWithPopup = authModule.signInWithPopup;
                        var onAuthStateChanged = authModule.onAuthStateChanged;
                        signInWithEmailAndPassword = authModule.signInWithEmailAndPassword;

                        console.log('Cuppa AI: Initializing Firebase app with config...');
                        var app = initializeApp(config);
                        auth = getAuth(app);
                        console.log('Cuppa AI: Firebase Auth initialized. Checking current user state...');

                        onAuthStateChanged(auth, function(user) {
                            if (user) {
                                console.log('Cuppa AI: User is logged in as ' + user.email + '. Fetching token...');
                                user.getIdToken(true).then(function(token) {
                                    window.cuppaIdToken = token;
                                    console.log('Cuppa AI: Token fetched successfully.');
                                    $('#auth-status').html('&#10003; Signed in as ' + user.email);
                                    $('#cuppa-auth-tabs').hide();
                                    $('#cuppa-auth-fields').hide();
                                    $('#btn-cuppa-login').hide();
                                    $('#subscription-status').html('Plan: <strong>Pro</strong> (Cuppa AI)');
                                    $('#aitutorial-main-content').fadeIn();
                                });

                                // Keep token fresh
                                setInterval(function() {
                                    user.getIdToken(true).then(function(token) {
                                        window.cuppaIdToken = token;
                                        console.log('Cuppa AI: Token refreshed silently.');
                                    }).catch(function(e) {
                                        console.warn('Cuppa AI: Token refresh failed', e);
                                    });
                                }, 55 * 60 * 1000);

                            } else {
                                console.log('Cuppa AI: User is NOT logged in. Showing sign-in options.');
                                window.cuppaIdToken = null;
                                setAuthBusy(false);
                                $('#auth-status').text('Sign in to start generating');
                                $('#cuppa-auth-tabs').show();
                                $('#cuppa-auth-fields').show();
                                $('#btn-cuppa-login').show();
                                $('#subscription-status').empty();
                                $('#aitutorial-main-content').hide();
                            }
                        });

                        $('#tab-signin').on('click', function() { switchAuthMode('signin'); });
                        $('#tab-signup').on('click', function() { switchAuthMode('signup'); });
                        $('#btn-cuppa-auth-submit').on('click', handleEmailAuthSubmit);
                        $('#cuppa-auth-password').on('keypress', function(e) {
                            if (e.which === 13) {
                                handleEmailAuthSubmit();
                            }
                        });

                        $('#btn-cuppa-login').on('click', function() {
                            console.log('Cuppa AI: Login button clicked. Triggering popup...');
                            var provider = new GoogleAuthProvider();
                            signInWithPopup(auth, provider).catch(function(error) {
                                console.error('Cuppa AI: Popup login failed', error);
                                Notification.addNotification({ message: 'Login failed: ' + error.message, type: 'error' });
                            });
                        });

                        // Gate the generation form: inject the current Firebase ID token on submit.
                        // Without this the hidden firebase_token field posts empty and the
                        // backend rejects the request with HTTP 401.
                        var forwarding = false;

                        $(document).on('submit', 'form', function(event) {
                            var $form = $(this);
                            var $firebaseField = $form.find('input[name="firebase_token"]');

                            // Only intercept the AI Tutorial generation form.
                            if ($firebaseField.length === 0) {
                                return;
                            }
                            // Allow the resubmission after the token has been injected.
                            if (forwarding) {
                                return;
                            }

                            event.preventDefault();

                            if (!auth) {
                                console.error('Cuppa AI: Firebase auth is not ready.');
                                $('#auth-status').html('<span style="color:#d9534f;">Authentication service unavailable. Reload the page and disable any adblocker.</span>');
                                return;
                            }

                            var user = auth.currentUser;
                            if (!user) {
                                console.warn('Cuppa AI: Submit blocked - user is not signed in.');
                                $('#auth-status').html('<span style="color:#d9534f;">Please sign in before generating.</span>');
                                $('#btn-cuppa-login').show();
                                return;
                            }

                            $('#auth-status').html('Fetching secure token...');

                            user.getIdToken(true).then(function(token) {
                                window.cuppaIdToken = token;
                                $firebaseField.val(token);
                                $('#auth-status').html('Token ready. Sending your request...');
                                forwarding = true;
                                $form[0].submit();
                            }).catch(function(err) {
                                console.error('Cuppa AI: Token fetch failed', err);
                                $('#auth-status').html('<span style="color:#d9534f;">Could not get secure token: ' + err.message + '</span>');
                            });
                        });
                    }).catch(function(err) {
                        console.error("Cuppa AI: Failed to load Firebase Auth script. Possibly blocked by network.", err);
                    });
                }).catch(function(err) {
                    console.error("Cuppa AI: Failed to load Firebase App script. Possibly blocked by adblocker.", err);
                    $('#auth-status').html('<span style="color:red;">Error connecting to authentication service. Try disabling your adblocker.</span>');
                });
            } catch (err) {
                console.error("Cuppa AI: Unexpected error during Firebase setup.", err);
                $('#auth-status').html('<span style="color:red;">Error connecting to authentication service. Try disabling your adblocker.</span>');
            }
        } else {
            console.log('Cuppa AI: Firebase already loaded globally.');
        }
    }

    return { init: init };
});