import { AppProvider, Page } from "@shopify/polaris";
import { BrowserRouter as Router, Routes, Route, Navigate } from "react-router-dom";
import enTranslations from '@shopify/polaris/locales/en.json';
import MissingApiKey from "./components/MissingApiKey.jsx";
import SettingsPage from "./components/SettingsPage.jsx"; // New settings page

const App = () => {
    const shopifyApiKey = document.querySelector('meta[name="shopify-api-key"]')?.getAttribute('content');

    if (!shopifyApiKey) {
        return (
            <AppProvider i18n={enTranslations}>
                <MissingApiKey />
            </AppProvider>
        );
    }

    return (
        <AppProvider i18n={enTranslations}>
            <Router>
                <Page>
                    <Routes>
                        <Route path="/" element={<Navigate to="/settings" replace />} />
                        <Route path="/settings" element={<SettingsPage />} />
                        <Route path="*" element={<Navigate to="/" replace />} /> {/* Redirect unknown routes */}
                    </Routes>
                </Page>
            </Router>
        </AppProvider>
    );
};

export default App;
