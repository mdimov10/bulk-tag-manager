import { createRoot } from "react-dom/client";
import App from "./App.jsx";
import '@shopify/polaris/build/esm/styles.css';

// Get the root element from the DOM
const rootElement = document.getElementById("app");

// Create a React root and render the App component
const root = createRoot(rootElement);
root.render(<App />);
