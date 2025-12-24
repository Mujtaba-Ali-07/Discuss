<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="card-title mb-0">📝 Give Us Your Feedback</h4>
                    <small class="opacity-75">Help us improve Discuss Project</small>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['user']['username'])): ?>
                        <form method="post" action="./server/requests.php" id="feedbackForm">
                            <!-- Feedback Type -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">What type of feedback do you have?</label>
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check card border p-3 h-100">
                                            <input class="form-check-input" type="radio" name="feedback_type" 
                                                   id="suggestion" value="suggestion" checked>
                                            <label class="form-check-label" for="suggestion">
                                                <div class="d-flex align-items-center">
                                                    <span class="fs-4 me-2">💡</span>
                                                    <div>
                                                        <strong>Suggestion</strong>
                                                        <p class="small text-muted mb-0">Share your ideas</p>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check card border p-3 h-100">
                                            <input class="form-check-input" type="radio" name="feedback_type" 
                                                   id="bug" value="bug">
                                            <label class="form-check-label" for="bug">
                                                <div class="d-flex align-items-center">
                                                    <span class="fs-4 me-2">🐛</span>
                                                    <div>
                                                        <strong>Bug Report</strong>
                                                        <p class="small text-muted mb-0">Report an issue</p>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check card border p-3 h-100">
                                            <input class="form-check-input" type="radio" name="feedback_type" 
                                                   id="feature" value="feature">
                                            <label class="form-check-label" for="feature">
                                                <div class="d-flex align-items-center">
                                                    <span class="fs-4 me-2">✨</span>
                                                    <div>
                                                        <strong>Feature Request</strong>
                                                        <p class="small text-muted mb-0">Request new features</p>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rating -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">How would you rate your experience?</label>
                                <div class="rating-container text-center">
                                    <div class="d-flex justify-content-center">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <div class="rating-star mx-1" data-rating="<?php echo $i; ?>">
                                                <input type="radio" id="star<?php echo $i; ?>" name="rating" 
                                                       value="<?php echo $i; ?>" <?php echo $i == 5 ? 'checked' : ''; ?>>
                                                <label for="star<?php echo $i; ?>" class="star-label">
                                                    <svg width="40" height="40" viewBox="0 0 24 24">
                                                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" 
                                                              fill="<?php echo $i <= 5 ? '#ffc107' : '#e4e5e9'; ?>"/>
                                                    </svg>
                                                </label>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="rating-text mt-2">
                                        <small class="text-muted">Click stars to rate</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Feedback Text -->
                            <div class="mb-4">
                                <label for="feedback_text" class="form-label fw-bold">Your Feedback / Suggestion</label>
                                <textarea class="form-control" id="feedback_text" name="feedback_text" 
                                          rows="6" placeholder="Tell us what you think about Discuss Project. 
What do you like? What can we improve? 
What features would you like to see added?" 
                                          required></textarea>
                                <div class="form-text">
                                    Be as detailed as possible. Your feedback helps us improve!
                                </div>
                            </div>

                            <!-- Suggestions Checklist -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">What improvements would you like to see? (Optional)</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" value="1" 
                                                   id="improve_ui" name="improvements[]">
                                            <label class="form-check-label" for="improve_ui">
                                                Better user interface design
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" value="1" 
                                                   id="mobile_app" name="improvements[]">
                                            <label class="form-check-label" for="mobile_app">
                                                Mobile app version
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" value="1" 
                                                   id="notifications" name="improvements[]">
                                            <label class="form-check-label" for="notifications">
                                                Push notifications
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" value="1" 
                                                   id="dark_mode" name="improvements[]">
                                            <label class="form-check-label" for="dark_mode">
                                                Dark mode theme
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" value="1" 
                                                   id="more_categories" name="improvements[]">
                                            <label class="form-check-label" for="more_categories">
                                                More categories/tags
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" value="1" 
                                                   id="file_uploads" name="improvements[]">
                                            <label class="form-check-label" for="file_uploads">
                                                File/image uploads in questions
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-grid">
                                <button type="submit" name="submit_feedback" class="btn btn-primary btn-lg">
                                    <span class="d-flex align-items-center justify-content-center">
                                        <span class="me-2">📤</span>
                                        Submit Feedback
                                    </span>
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    Thank you for helping us improve! Your feedback is anonymous and will be reviewed by our team.
                                </small>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <span class="display-1">🔒</span>
                            </div>
                            <h4>Login Required</h4>
                            <p class="text-muted">Please login to submit feedback and help us improve.</p>
                            <div class="mt-4">
                                <a href="?login=true" class="btn btn-primary me-2">Login</a>
                                <a href="?signup=true" class="btn btn-outline-primary">Sign Up</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Feedback (Admin View) -->
            <?php if (isset($_SESSION['user']['username'])): ?>
                <?php
                include './common/db.php';
                $user_email = $_SESSION['user']['useremail'];
                $user_stmt = $conn->prepare("SELECT id FROM users WHERE useremail = ?");
                $user_stmt->bind_param("s", $user_email);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                $user_data = $user_result->fetch_assoc();
                $user_id = $user_data['id'];

                // Check if user has submitted feedback before
                $feedback_check = $conn->prepare("SELECT * FROM feedback WHERE user_id = ? ORDER BY created_at DESC LIMIT 3");
                $feedback_check->bind_param("i", $user_id);
                $feedback_check->execute();
                $feedback_result = $feedback_check->get_result();
                ?>

                <?php if ($feedback_result->num_rows > 0): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">📋 Your Previous Feedback</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php while ($feedback = $feedback_result->fetch_assoc()): ?>
                                    <div class="list-group-item border-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="d-flex align-items-center mb-1">
                                                    <?php 
                                                    $type_icon = [
                                                        'suggestion' => '💡',
                                                        'bug' => '🐛',
                                                        'feature' => '✨'
                                                    ];
                                                    $type_text = [
                                                        'suggestion' => 'Suggestion',
                                                        'bug' => 'Bug Report',
                                                        'feature' => 'Feature Request'
                                                    ];
                                                    ?>
                                                    <span class="me-2"><?php echo $type_icon[$feedback['feedback_type']]; ?></span>
                                                    <strong><?php echo $type_text[$feedback['feedback_type']]; ?></strong>
                                                    <span class="badge bg-light text-dark ms-2">
                                                        <?php echo str_repeat('⭐', $feedback['rating']); ?>
                                                    </span>
                                                </div>
                                                <p class="mb-1"><?php echo htmlspecialchars($feedback['feedback_text']); ?></p>
                                                <small class="text-muted">
                                                    Submitted <?php echo date('M j, Y', strtotime($feedback['created_at'])); ?>
                                                </small>
                                            </div>
                                            <span class="badge 
                                                <?php echo $feedback['feedback_type'] == 'suggestion' ? 'bg-info' : 
                                                       ($feedback['feedback_type'] == 'bug' ? 'bg-danger' : 'bg-success'); ?>">
                                                <?php echo ucfirst($feedback['feedback_type']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Feedback Success Modal -->
<div class="modal fade" id="feedbackSuccessModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-5">
                <div class="mb-4">
                    <span class="display-1 text-success">✅</span>
                </div>
                <h4>Thank You!</h4>
                <p class="text-muted">Your feedback has been submitted successfully.</p>
                <p class="small text-muted">We appreciate your input and will use it to improve Discuss Project.</p>
                <div class="mt-4">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Continue Browsing</button>
                </div>
            </div>
        </div>
    </div>
</div>