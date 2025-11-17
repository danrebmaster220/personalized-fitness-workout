import React from "react";
import "../styles/ConfirmModal.css";

export default function ConfirmModal({ 
  title = "Confirm", 
  body = "Are you sure?", 
  onCancel, 
  onConfirm,
  hideConfirm = false,
  confirmText = "Confirm"
}) {
  return (
    <div className="confirm-overlay">
      <div className="confirm-box">

        <h3>{title}</h3>
        <p>{body}</p>

        <div className="confirm-actions">
          {hideConfirm ? (
            <button className="confirm-yes" onClick={onCancel}>
              {confirmText}
            </button>
          ) : (
            <>
              <button className="confirm-cancel" onClick={onCancel}>Cancel</button>
              <button className="confirm-yes" onClick={onConfirm}>
                {confirmText}
              </button>
            </>
          )}
        </div>

      </div>
    </div>
  );
}
