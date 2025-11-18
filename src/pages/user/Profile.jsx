import { useEffect, useState } from "react";
import axios from "axios";
import "../../styles/Profile.css";

const API_BASE = "/api";

export default function Profile() {
  const storedUser = JSON.parse(localStorage.getItem("user"));
  const [user, setUser] = useState(storedUser);
  const [loading, setLoading] = useState(false);

  const [form, setForm] = useState({
    firstName: storedUser?.FirstName || "",
    lastName: storedUser?.LastName || "",
    age: storedUser?.Age || "",
    height: storedUser?.Height || "",
    weight: storedUser?.Weight || "",
    gender: storedUser?.Gender || "",
    fitnessLevel: storedUser?.Fitness_Level || "",
    activityLevel: storedUser?.Activity_Level || "",
  });

  const [profileImage, setProfileImage] = useState(null);
  const [previewImage, setPreviewImage] = useState(
    storedUser?.Profile_Image
      ? `${import.meta.env.VITE_API_URL}${storedUser.Profile_Image}`
      : "https://i.pravatar.cc/150?u=" + storedUser?.Email
  );

  const handleInput = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  // ---------- Upload Profile Image ----------
  const handleImageChange = (e) => {
    const file = e.target.files[0];
    setProfileImage(file);
    setPreviewImage(URL.createObjectURL(file));
  };

  const uploadImage = async () => {
    if (!profileImage) return;

    const formData = new FormData();
    formData.append("image", profileImage);
    formData.append("userId", storedUser.User_ID);

    const res = await axios.post(`${API_BASE}/index.php?route=user&action=uploadImage`, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });

    if (res.data.success) {
      const updated = { ...storedUser, Profile_Image: res.data.path };
      localStorage.setItem("user", JSON.stringify(updated));
      window.dispatchEvent(new Event("user-updated"));
      alert("Profile image updated!");
    }
  };

  // Save Profile Info
  const saveProfile = async () => {
    setLoading(true);

    const res = await axios.post(
      `${API_BASE}/index.php?route=user&action=updateProfile`,
      { userId: user.User_ID, ...form }
    );

    setLoading(false);

    if (res.data.success) {
      const updated = { ...storedUser, ...form };
      localStorage.setItem("user", JSON.stringify(updated));

      // update sidebar too
      window.dispatchEvent(new Event("user-updated"));

      alert("Profile updated!");
    }
  };

  return (
    <div className="profile-page">

      <h1 className="profile-title">My Profile</h1>

      {/* Profile Picture */}
      <section className="profile-section">
        <h2>Profile Picture</h2>

        <div className="profile-image-box">
          <img src={previewImage} alt="Profile" className="profile-preview" />

          <div>
            <input type="file" onChange={handleImageChange} />
            <button className="btn-primary" onClick={uploadImage}>
              Upload Image
            </button>
          </div>
        </div>
      </section>

      {/* Personal Information */}
      <section className="profile-section">
        <h2>Personal Information</h2>

        <div className="profile-grid">
          <label>First Name
            <input name="firstName" value={form.firstName} onChange={handleInput} />
          </label>

          <label>Last Name
            <input name="lastName" value={form.lastName} onChange={handleInput} />
          </label>

          <label>Age
            <input name="age" type="number" value={form.age} onChange={handleInput} />
          </label>

          <label>Gender
            <select name="gender" value={form.gender} onChange={handleInput}>
              <option>male</option>
              <option>female</option>
              <option>other</option>
            </select>
          </label>
        </div>
      </section>

      {/* Fitness Section */}
      <section className="profile-section">
        <h2>Fitness Information</h2>

        <div className="profile-grid">
          <label>Height (cm)
            <input name="height" value={form.height} onChange={handleInput} />
          </label>

          <label>Weight (kg)
            <input name="weight" value={form.weight} onChange={handleInput} />
          </label>

          <label>Fitness Level
            <select name="fitnessLevel" value={form.fitnessLevel} onChange={handleInput}>
              <option>beginner</option>
              <option>intermediate</option>
              <option>advanced</option>
            </select>
          </label>

          <label>Activity Level
            <select name="activityLevel" value={form.activityLevel} onChange={handleInput}>
              <option>low</option>
              <option>moderate</option>
              <option>high</option>
            </select>
          </label>
        </div>
      </section>

      {/* Save Button */}
      <div className="profile-actions">
        <button className="btn-primary" onClick={saveProfile}>
          {loading ? "Saving..." : "Save Changes"}
        </button>
      </div>
    </div>
  );
}
