import React, { useState } from "react";
import "../styles/Home.css";
import FeatureCard from "../components/FeatureCard";
import Button from "../components/Button";

const Home = () => {
  const [loading, setLoading] = useState(false);

  const handleGenerateClick = () => {
    const token = localStorage.getItem("userToken");

    // Simulate loading when user clicks
    setLoading(true);

    setTimeout(() => {
      setLoading(false);
      if (token) {
        // Scroll to modal/form
        document
          .getElementById("generate-workout")
          ?.scrollIntoView({ behavior: "smooth" });
      } else {
        window.location.href = "/login";
      }
    }, 1500);
  };

  return (
    <div className="home-container">
      {/* HERO SECTION */}
      <section className="hero">
        <div className="overlay"></div>
        <div className="hero-content">
          <h1>
            Sync Your Goals. Track Your Progress.
            <br />
            <span>Shape Your Results.</span>
          </h1>
          <p>
            Your personalized fitness and workout companion designed to help you
            reach your goals faster and smarter.
          </p>

          <Button text="Get Started" onClick={handleGenerateClick} loading={loading} />
        </div>
      </section>

      {/* FEATURES SECTION */}
      <section className="features" id="features">
        <h2>Features</h2>
        <p>
          Our platform uses intelligent APIs to generate a customized workout
          and meal plan tailored just for you.
        </p>

        <div className="feature-grid">
          <FeatureCard
            title="Personalized Workout Generator"
            description="Automatically creates a workout plan based on your age, weight, height, fitness level, and goals."
            icon="💪"
          />
          <FeatureCard
            title="Meal Recommendations"
            description="Get healthy meal suggestions to complement your fitness routine and goals."
            icon="🍎"
          />
          <FeatureCard
            title="Progress Tracker"
            description="Monitor your progress and see how far you’ve come on your fitness journey."
            icon="📊"
          />
          <FeatureCard
            title="PDF & History"
            description="Download your custom workout plan and view all your previously generated routines anytime."
            icon="📄"
          />
        </div>
      </section>

      {/* CTA SECTION */}
      <section className="cta-section" id="generate-workout">
        <h3>Are you ready to build your personalized workout plan?</h3>
        <Button
          text="Generate My Workout"
          onClick={handleGenerateClick}
          variant="secondary"
          loading={loading}
        />
      </section>

      {/* FOOTER */}
      <footer className="footer">
      <div className="footer-container">
        {/* Logo */}
        <div className="footer-logo">
          FitNes<span className="footer-logo-accent">+</span>
        </div>

        {/* Footer Navigation */}
        <div className="footer-links">
          <a href="/">Home</a>
          <a href="/features">Features</a>
          <a href="/about">About</a>
          <a href="/contact">Contact</a>
        </div>

        {/* Socials */}
        <div className="footer-socials">
          <a href="#" title="Facebook"><i className="fab fa-facebook-f"></i></a>
          <a href="#" title="Instagram"><i className="fab fa-instagram"></i></a>
          <a href="#" title="Twitter"><i className="fab fa-twitter"></i></a>
        </div>
      </div>

      {/* Bottom section */}
      <div className="footer-bottom">
        <p>© {new Date().getFullYear()} FitNes+. All rights reserved.</p>
      </div>
    </footer>
    </div>
  );
};

export default Home;
