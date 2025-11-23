import React, { useEffect } from "react";
import { createPortal } from "react-dom";
import "../styles/LogoutConfirmModal.css";

export default function LogoutConfirmModal({ isOpen, onConfirm, onCancel, message }) {
  // Prevent background scrolling while modal is open. Hook must run unconditionally.
  useEffect(() => {
    const prev = document.body.style.overflow;
    if (isOpen) document.body.style.overflow = "hidden";
    return () => { document.body.style.overflow = prev; };
  }, [isOpen]);

  // Render nothing when closed
  if (!isOpen) return null;

  const modal = (
    <div className="logout-modal-overlay" role="dialog" aria-modal="true">
      <div className="logout-modal">
        <h3>Confirm Logout</h3>
        <p>{message || "Are you sure you want to logout?"}</p>

        <div className="logout-modal-actions">
          <button className="btn-cancel" onClick={onCancel}>Cancel</button>
          <button className="btn btn-danger" onClick={onConfirm}>Logout</button>
        </div>
      </div>
    </div>
  );

  // Render into document.body to avoid stacking context issues
  return createPortal(modal, document.body);
}
