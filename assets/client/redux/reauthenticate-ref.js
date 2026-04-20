/**
 * Mutable ref for the reauthenticate callback.
 * Set by App component, called by base-query when a 401 is received.
 * This bridges the Redux middleware layer (outside React) with the React tree.
 */
const reauthenticateRef = { current: () => {} };

export default reauthenticateRef;
