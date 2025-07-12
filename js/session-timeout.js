document.addEventListener('DOMContentLoaded', function() {
    if (!document.body.hasAttribute('data-logged-in')) return;
    
    const timeoutMinutes = 1;
    const warningMinutes = 0.5; 
    
    let timeoutWarningShown = false;
    let inactivityTimeout;
    
    const resetTimer = function() {
        clearTimeout(inactivityTimeout);
        
        if (timeoutWarningShown) {
            const warningElement = document.getElementById('session-timeout-warning');
            if (warningElement) {
                warningElement.style.display = 'none';
                timeoutWarningShown = false;
            }
        }
        
        inactivityTimeout = setTimeout(function() {
            showTimeoutWarning();
        }, (timeoutMinutes - warningMinutes) * 60 * 1000);
    };
    
    const showTimeoutWarning = function() {
        timeoutWarningShown = true;
        
        let warningElement = document.getElementById('session-timeout-warning');
        if (!warningElement) {
            warningElement = document.createElement('div');
            warningElement.id = 'session-timeout-warning';
            warningElement.classList.add('timeout-warning');
            warningElement.innerHTML = `
                <div class="timeout-warning-content">
                    <p><strong>Session Timeout Warning</strong></p>
                    <p>Your session will expire in ${warningMinutes * 60} seconds due to inactivity.</p>
                    <button id="extend-session" class="btn">Stay Logged In</button>
                </div>
            `;
            document.body.appendChild(warningElement);
            
            document.getElementById('extend-session').addEventListener('click', function() {
               
                fetch('refresh_session.php', {
                    method: 'POST',
                    credentials: 'same-origin'
                }).then(response => {
                    if (response.ok) return response.json();
                    throw new Error('Network response was not ok');
                }).then(data => {
                    if (data.success) {
                        warningElement.style.display = 'none';
                        timeoutWarningShown = false;
                        resetTimer();
                    }
                }).catch(error => {
                    console.error('Error refreshing session:', error);
                });
            });
        } else {
            warningElement.style.display = 'block';
        }
        
        setTimeout(function() {
            window.location.href = 'logout.php?timeout=1';
        }, warningMinutes * 60 * 1000);
    };
    
    ['mousemove', 'keypress', 'click', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, resetTimer, false);
    });
    
    resetTimer();
});
