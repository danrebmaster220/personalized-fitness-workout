import React from "react";
import "../styles/ConfirmModal.css";

export default function ConfirmModal({ title = "Confirm", body = "Are you sure?", onCancel, onConfirm }) {
  return (
    <div className="wm-backdrop confirm-modal">
      <div className="confirm-modal-container">
        <h3>{title}</h3>
        <p>{body}</p>

        <div className="confirm-modal-buttons">
          <button className="btn-cancel" onClick={onCancel}>Cancel</button>
          <button className="btn-confirm" onClick={onConfirm}>Confirm</button>
        </div>
      </div>
    </div>
  );
}
