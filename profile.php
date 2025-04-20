<?php
require 'db.php'; // Include the database connection file

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle account deletion
    if (isset($_POST['delete_account']) && isset($_POST['userId'])) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        session_destroy();
        header("Location: login.php");
        exit();
    }

    // Handle profile update
    $success = '';
    if (isset($_POST['update_profile']) && isset($_POST['userId'])) {
        $user_id = $_POST['userId'];
        $name = $_POST['name'];
        $prenom = $_POST['prenom'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        $address = $_POST['address'];

        // Handle profile picture upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0755, true); // Create the directory if it doesn't exist
            }
            $target_file = $target_dir . basename($_FILES["profile_pic"]["name"]);
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Check if the file is an image
            $check = getimagesize($_FILES["profile_pic"]["tmp_name"]);
            if ($check !== false) {
                // Allow certain file formats
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($imageFileType, $allowed_types)) {
                    // Check file size (limit to 2MB)
                    if ($_FILES["profile_pic"]["size"] < 2000000) {
                        // Generate a unique filename to avoid conflicts
                        $unique_filename = uniqid() . '.' . $imageFileType;
                        $target_file = $target_dir . $unique_filename;

                        // Try to move the uploaded file to the target directory
                        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                            // Update the profile picture in the database
                            $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                            $stmt->execute([$target_file, $user_id]);
                        } else {
                            $error = "Sorry, there was an error uploading your file.";
                        }
                    } else {
                        $error = "Sorry, your file is too large (max 2MB).";
                    }
                } else {
                    $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                }
            } else {
                $error = "File is not an image.";
            }
        }

        // Update user data
        $stmt = $pdo->prepare("UPDATE users SET name = ?, prenom = ?, phone = ?, email = ?, address = ? WHERE id = ?");
        $stmt->execute([$name, $prenom, $phone, $email, $address, $user_id]);

        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $success = "Profile updated successfully!";
        header("Location: " . ($user['role'] === 'admin' ? 'admin_dashboard.php?page=profile&userId<?=$userId?>' : ($user['role'] === 'supplier' ? 'suppliers_dashboard.php?page=profile&userId<?=$userId?>' : 'index.php??page=profile&userId<?=$userId?>page=home')));
        exit();
    }
}
?>

<style>
    .profile-pic {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 20px;
    }

    .card {
        border-radius: 15px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        color: white;
        border-radius: 15px 15px 0 0;
    }

    .btn-primary {
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        border: none;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #2575fc 0%, #6a11cb 100%);
    }
</style>


<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Profile</h3>
                </div>
                <div class="card-body">
                    <!-- Display success message if profile is updated -->
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>

                    <!-- Display error message if file upload fails -->
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <!-- Profile Picture -->
                    <div class="text-center">
                        <img src="<?= $user['profile_pic'] ? $user['profile_pic'] : 'https://via.placeholder.com/150' ?>" alt="Profile Picture" class="profile-pic">
                    </div>

                    <!-- Profile Information -->
                    <form action="profile.php" method="POST" enctype="multipart/form-data">
                        <!-- Profile Picture -->
                        <div class="mb-3">
                            <label for="profile_pic" class="form-label">Change Profile Picture</label>
                            <input type="file" class="form-control" id="profile_pic" name="profile_pic" accept="image/*">
                        </div>

                        <!-- Nom and Prenom -->
                        <div class="row mb-6">
                            
                            <div class="col-md-6">
                                <label for="name" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= $user['name'] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="prenom" class="form-label">Prenom</label>
                                <input type="text" class="form-control" id="prenom" name="prenom" value="<?= $user['prenom'] ?>" required>
                            </div>
                        </div>

                        <!-- Phone and Email -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?= $user['phone'] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= $user['email'] ?>" required>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?= $user['address'] ?>" required>
                        </div>

                        <!-- Hidden User ID -->
                        <input type="hidden" name="userId" value="<?= $_SESSION['user_id'] ?>">

                        <!-- Submit Button -->
                        <button type="submit" name="update_profile" class="btn btn-primary w-100">Update Profile</button>
                    </form>

                    <!-- Delete Account Button -->
                    <form action="profile.php" method="POST" class="mt-3">
                        <button type="submit" name="delete_account" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to delete your account? This action cannot be undone.')">Delete Account</button>
                    </form>


                </div>
            </div>
        </div>
    </div>
</div>