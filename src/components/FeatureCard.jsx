import React from "react";

const FeatureCard = ({ title, description, icon }) => {
  return (
    <div className="feature-card">
      {icon && <div className="feature-icon">{icon}</div>}
      <h3>{title}</h3>
      <p>{description}</p>
    </div>
  );
};

export default FeatureCard;
