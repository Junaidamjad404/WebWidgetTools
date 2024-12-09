import {
    Page,
    Layout,
    InlineGrid,
    TextField,
    Box,
    ColorPicker,
    Button,
} from "@shopify/polaris";
import { TitleBar } from "@shopify/app-bridge-react";
import { useTranslation } from "react-i18next";
import { useState, useCallback } from "react";

import { trophyImage } from "../assets";

import { ProductsCard } from "../components";

export default function HomePage() {
    const { t } = useTranslation();
    const [value, setValue] = useState("");
    const [color, setColor] = useState({
        hue: 120,
        brightness: 1,
        saturation: 1,
    });
    const [text, setText] = useState(""); // User text input
    const [email, setEmail] = useState(""); // Email input state

    const handleChange = useCallback((newValue) => setValue(newValue), []);
    const handleTextChange = useCallback((newValue) => setText(newValue), []);
    const handleEmailChange = useCallback((newValue) => setEmail(newValue), []);

    const hslColor = `hsl(${color.hue}, ${color.saturation * 100}%, ${color.brightness * 100}%)`;

    const handleSubmit =  (event) => {
        event.preventDefault();

        // Create the data to send in the request
        const requestData = {
            fontSize: value || 16,
            backgroundColor: hslColor,
            textContent: text,
            email: email,
        };

        try {
            const response = fetch("https://web.test/api/set_metafield", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(requestData),
            });

            if (response.ok) {
                // Handle successful response
                alert("Data submitted successfully!");
            } else {
                // Handle error response
                alert("There was an error submitting the data.");
            }
        } catch (error) {
            console.error("Error:", error);
            alert("Failed to submit data.");
        }
    };

    return (
        <Page fullWidth>
            <TitleBar title="Dashboard" primaryAction={null} />
            <Layout>
                <Layout.Section>
                    <InlineGrid columns={["oneThird", "twoThirds"]}>
                        <Box padding={200}>
                            <TextField
                                label="Font Size (px)"
                                type="number"
                                value={value}
                                onChange={handleChange}
                                autoComplete="off"
                                placeholder="Enter font size"
                                style={{ marginBottom: "10px" }}
                            />
                            <ColorPicker onChange={setColor} color={color} />
                            <TextField
                                label="Text Content"
                                value={text}
                                onChange={handleTextChange}
                                autoComplete="off"
                                placeholder="Enter text"
                            />
                            <TextField
                                label="Email"
                                value={email}
                                onChange={handleEmailChange}
                                autoComplete="off"
                                placeholder="Enter your email"
                            />
                            <Button primary onClick={handleSubmit}>
                                Submit
                            </Button>
                        </Box>

                        <Box padding={1000}>
                            <span
                                style={{
                                    fontSize: `${value || 16}px`,
                                    backgroundColor: hslColor,
                                    padding: "10px",
                                    display: "inline-block",
                                    borderRadius: "4px",
                                }}
                            >
                                {text}
                            </span>
                        </Box>
                    </InlineGrid>
                </Layout.Section>
            </Layout>
        </Page>
    );
}
