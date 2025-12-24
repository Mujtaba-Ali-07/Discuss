<div class="container">
    <h2 class="heading">Login</h2>
    <form method="post" action="./server/requests.php">
        <div class="offset-sm-3 col-sm-6 margin-top-15">
            <label for="useremail" class="form-label">User Email</label>
            <input type="email" name="useremail" class="form-control" id="useremail" placeholder="Enter the Email" required>
        </div>
        <div class="offset-sm-3 col-sm-6 margin-top-15">
            <label for="userpassword" class="form-label">User Password</label>
            <input type="password" name="userpassword" class="form-control" id="userpassword" placeholder="Enter the Password" required>
        </div>

        <div class="offset-sm-3 col-sm-6 margin-top-15">
            <button type="submit" name="login" class="btn btn-primary">Login</button>
        </div>
    </form>
</div>