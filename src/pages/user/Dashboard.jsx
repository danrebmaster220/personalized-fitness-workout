import React from "react";
import "../../styles/Dashboard.css";

export default function Dashboard() {
  const user = JSON.parse(localStorage.getItem("user"));

  return (
    <div>
      <header className="hero-header">
        <div className="hero-content">
          <h1 className="hero-title">
            Welcome, {user?.First_Name || "Champion"} 💪
          </h1>
          <p className="hero-subtitle">
            Track your fitness and generate AI-powered workouts.
          </p>
        </div>
      </header>

      <section className="content-area">
        <div className="empty-state">
          <i>🔥</i>
          <h3>No recent workout yet</h3>
          <p>Generate your first personalized workout plan now.</p>
        </div>
      </section>
    </div>
  );
}
