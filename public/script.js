// AJAX Helper Function
function makeAjaxRequest(url, data, successCallback, errorCallback) {
    const formData = new FormData();
    for (const key in data) {
        formData.append(key, data[key]);
    }

    console.log('Making AJAX request to:', url);
    console.log('Request data:', data);

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text().then(text => {
            console.log('Raw response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON:', e);
                throw new Error('Invalid JSON response');
            }
        });
    })
    .then(result => {
        console.log('Parsed response:', result);
        if (result.success) {
            if (successCallback) successCallback(result.data);
        } else {
            if (errorCallback) errorCallback(result.message);
            showNotification(result.message, 'error');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        if (errorCallback) errorCallback('Network error occurred');
        showNotification('Network error occurred. Check console for details.', 'error');
    });
}

// Notification System
function showNotification(message, type = 'success') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.custom-notification');
    existingNotifications.forEach(notif => notif.remove());

    const notification = document.createElement('div');
    notification.className = `custom-notification alert alert-${type === 'error' ? 'danger' : 'success'}`;
    notification.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <span>${message}</span>
            <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    // Add styles
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        zIndex: '9999',
        minWidth: '300px',
        maxWidth: '400px',
        animation: 'slideIn 0.3s ease-out',
        boxShadow: '0 4px 12px rgba(0,0,0,0.15)'
    });
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }
    }, 3000);
}

// Like Button Handler
function setupLikeButtons() {
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const questionId = this.getAttribute('data-question-id');
            const likeCountSpan = this.querySelector('.like-count');
            const likeIcon = this.querySelector('.like-icon');
            
            // Add loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            this.disabled = true;
            
            makeAjaxRequest(
                './server/requests.php',
                {
                    like_question_ajax: true,
                    question_id: questionId
                },
                (data) => {
                    // Update like count
                    if (likeCountSpan) {
                        likeCountSpan.textContent = data.like_count;
                    }
                    
                    // Update button appearance
                    if (data.liked) {
                        this.classList.remove('btn-outline-primary');
                        this.classList.add('btn-primary');
                        likeIcon.textContent = '❤️';
                        showNotification('Liked!');
                    } else {
                        this.classList.remove('btn-primary');
                        this.classList.add('btn-outline-primary');
                        likeIcon.textContent = '👍';
                        showNotification('Unliked');
                    }
                    
                    // Update popular questions progress bar if exists
                    updatePopularityProgress(questionId, data.like_count);
                },
                (error) => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }
            );
        });
    });
}

// Comment Form Handler
function setupCommentForms() {
    document.querySelectorAll('.comment-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const questionId = this.getAttribute('data-question-id');
            const commentInput = this.querySelector('.comment-input');
            
            // Find comments container and comment count within the same card
            const card = this.closest('.card');
            const commentsContainer = card.querySelector('.comments-container');
            const commentCountSpan = card.querySelector('.comment-count');
            
            if (!commentInput.value.trim()) {
                showNotification('Comment cannot be empty', 'error');
                return;
            }
            
            // Add loading state
            const submitButton = this.querySelector('[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            submitButton.disabled = true;
            
            console.log('Submitting comment for question:', questionId);
            console.log('Comment text:', commentInput.value);
            
            makeAjaxRequest(
                './server/requests.php',
                {
                    add_comment_ajax: true,
                    question_id: questionId,
                    comment_text: commentInput.value
                },
                (data) => {
                    console.log('Comment response:', data);
                    
                    // Add new comment to the top of comments container
                    if (commentsContainer) {
                        const commentDiv = document.createElement('div');
                        commentDiv.innerHTML = data.comment_html;
                        
                        // Check if "No comments yet" message exists and remove it
                        const noCommentsMsg = commentsContainer.querySelector('p.text-muted');
                        if (noCommentsMsg && noCommentsMsg.textContent.includes('No comments yet')) {
                            noCommentsMsg.remove();
                        }
                        
                        commentsContainer.insertBefore(commentDiv.firstElementChild, commentsContainer.firstChild);
                    }
                    
                    // Update comment count
                    if (commentCountSpan) {
                        commentCountSpan.textContent = data.comment_count;
                    }
                    
                    // Clear input
                    commentInput.value = '';
                    
                    // Reset button
                    submitButton.innerHTML = originalText;
                    submitButton.disabled = false;
                    
                    showNotification('Comment added successfully!');
                },
                (error) => {
                    console.error('Comment error:', error);
                    submitButton.innerHTML = originalText;
                    submitButton.disabled = false;
                    showNotification('Error adding comment: ' + error, 'error');
                }
            );
        });
    });
}

// Update Popularity Progress Bar
function updatePopularityProgress(questionId, newLikeCount) {
    const progressBar = document.querySelector(`.progress-bar[data-question-id="${questionId}"]`);
    if (progressBar) {
        const maxLikes = progressBar.getAttribute('data-max-likes') || 100;
        const percentage = Math.min(100, (newLikeCount / maxLikes) * 100);
        progressBar.style.width = `${percentage}%`;
        progressBar.setAttribute('aria-valuenow', percentage);
        
        // Update like count text
        const likeCountText = progressBar.closest('.card').querySelector('.like-count-text');
        if (likeCountText) {
            likeCountText.textContent = newLikeCount;
        }
    }
}

// Toggle Comments Section
function setupCommentToggles() {
    document.querySelectorAll('.comment-toggle-btn').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-bs-target');
            const target = document.querySelector(targetId);
            
            if (target) {
                // If comments container is empty, we might want to load comments via AJAX
                const commentsContainer = target.querySelector('.comments-container');
                if (commentsContainer && commentsContainer.children.length === 0) {
                    // You could implement lazy loading of comments here
                }
            }
        });
    });
}

// Feedback Page Interactions
function setupFeedbackPage() {
    // Star rating interaction
    document.querySelectorAll('.rating-star').forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.getAttribute('data-rating');
            
            // Update all stars
            document.querySelectorAll('.rating-star').forEach(s => {
                const starId = s.getAttribute('data-rating');
                const starSvg = s.querySelector('svg path');
                if (starId <= rating) {
                    starSvg.setAttribute('fill', '#ffc107');
                } else {
                    starSvg.setAttribute('fill', '#e4e5e9');
                }
            });
            
            // Update selected radio button
            document.querySelector(`#star${rating}`).checked = true;
            
            // Update rating text
            const ratingText = document.querySelector('.rating-text small');
            const ratingMessages = [
                "Needs improvement",
                "Below average",
                "Average",
                "Good",
                "Excellent!"
            ];
            ratingText.textContent = ratingMessages[rating - 1];
        });
    });

    // Feedback type card selection
    document.querySelectorAll('.form-check.card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.classList.contains('form-check-input')) {
                const radioInput = this.querySelector('.form-check-input');
                radioInput.checked = true;
                
                // Update all cards
                document.querySelectorAll('.form-check.card').forEach(c => {
                    c.classList.remove('border-primary', 'bg-primary-light');
                });
                
                // Highlight selected card
                this.classList.add('border-primary', 'bg-primary-light');
            }
        });
    });

    // Show success modal if URL has success parameter
    if (window.location.search.includes('feedback=success')) {
        const modal = new bootstrap.Modal(document.getElementById('feedbackSuccessModal'));
        modal.show();
        
        // Clean URL without reload
        const url = new URL(window.location);
        url.searchParams.delete('feedback');
        window.history.replaceState({}, '', url);
    }

    // Character counter for feedback text
    const feedbackTextarea = document.getElementById('feedback_text');
    if (feedbackTextarea) {
        const charCounter = document.createElement('div');
        charCounter.className = 'form-text text-end';
        charCounter.id = 'charCounter';
        charCounter.textContent = '0/1000 characters';
        feedbackTextarea.parentNode.appendChild(charCounter);

        feedbackTextarea.addEventListener('input', function() {
            const length = this.value.length;
            charCounter.textContent = `${length}/1000 characters`;
            
            if (length > 1000) {
                charCounter.classList.add('text-danger');
                this.classList.add('is-invalid');
            } else {
                charCounter.classList.remove('text-danger');
                this.classList.remove('is-invalid');
            }
        });
    }
}

// Initialize feedback page
if (window.location.search.includes('feedback=true')) {
    document.addEventListener('DOMContentLoaded', setupFeedbackPage);
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    setupLikeButtons();
    setupCommentForms();
    setupCommentToggles();
    
    // Add CSS animations for notifications
    if (!document.querySelector('#notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            
            .spinner-border {
                vertical-align: middle;
            }
            
            .btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }
        `;
        document.head.appendChild(style);
    }
});