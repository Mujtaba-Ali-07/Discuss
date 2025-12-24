<div class="container">
    <h2 class="heading">Signup</h2>
    <form method="post" action="./server/requests.php">
        <div class="offset-sm-3 col-sm-6 margin-top-15">
            <label for="username" class="form-label">User Name</label>
            <input type="input" name="username" class="form-control" id="username" placeholder="Enter the Username">
        </div>
        <div class="offset-sm-3 col-sm-6 margin-top-15">
            <label for="useremail" class="form-label">User Email</label>
            <input type="email" name="useremail" class="form-control" id="useremail" placeholder="Enter the Email">
        </div>
        <div class="offset-sm-3 col-sm-6 margin-top-15">
            <label for="userpassword" class="form-label">User Password</label>
            <input type="password" name="userpassword" class="form-control" id="userpassword" placeholder="Enter the Password">
        </div>
        <div class="offset-sm-3 col-sm-6 margin-top-15">
            <label for="usercountry" class="form-label">User Country</label>
            <input type="input" name="usercountry" class="form-control" id="usercountry" placeholder="Enter the Country">
        </div>

        <div class="offset-sm-3 col-sm-6 margin-top-15">
            <button type="submit" name="signup" class="btn btn-primary">Signup</button>
        </div>
    </form>
</div>