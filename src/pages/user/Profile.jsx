import React from "react";
import "../../styles/Profile.css";

export default function Profile() {
  const user = JSON.parse(localStorage.getItem("user"));

  return (
    <div className="profile-page">
      <header className="hero-header">
        <div className="hero-content">
          <h1 className="hero-title">Profile</h1>
          <p className="hero-subtitle">Manage your account details</p>
        </div>
      </header>

      <section className="content-area">
        <div className="profile-card">
          <p><b>Name:</b> {user?.FirstName} {user?.LastName}</p>
          <p><b>Email:</b> {user?.Email}</p>
          <p><b>Gender:</b> {user?.Gender}</p>
          <p><b>Height:</b> {user?.Height} cm</p>
          <p><b>Weight:</b> {user?.Weight} kg</p>
          <p><b>Age:</b> {user?.Age}</p>
          <p><b>Fitness Level:</b> {user?.Fitness_Level}</p>
          <p><b>Activity Level:</b> {user?.Activity_Level || "Not set"}</p>
        </div>
      </section>
    </div>
  );
}
