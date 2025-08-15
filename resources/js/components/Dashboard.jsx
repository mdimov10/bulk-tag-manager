import { Button, Card } from "@shopify/polaris";
import { useNavigate } from "react-router-dom";

const Dashboard = () => {
    const navigate = useNavigate();

    return (
        <Card sectioned>
            <h1>Welcome to the AI Product Description Generator</h1>
            <Button primary onClick={() => navigate("/products")}>
                Go to Product Selection
            </Button>
        </Card>
    );
};

export default Dashboard;
