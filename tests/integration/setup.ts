import { TEST_DATABASE_URL } from "./env";
process.env.DATABASE_URL = TEST_DATABASE_URL;
process.env.APP_SECRET ??= "test-secret-test-secret-test-secret-123456";
process.env.APP_URL ??= "http://localhost:3000";
