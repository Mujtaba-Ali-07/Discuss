<div class="container mt-4">
    <h2 class="heading">Ask a Question</h2>
    <form method="post" action="./server/requests.php">
        <div class="mb-3">
            <label for="questionTitle" class="form-label">Question Title</label>
            <input type="text" name="question_title" class="form-control" id="questionTitle"
                placeholder="Enter your question title" required>
        </div>
        <div class="mb-3">
            <label for="questionDescription" class="form-label">Question Description</label>
            <textarea name="question_description" class="form-control" id="questionDescription"
                rows="5" placeholder="Describe your question in detail..." required></textarea>
        </div>
        <button type="submit" name="ask_question" class="btn btn-primary">Post Question</button>
    </form>
</div>