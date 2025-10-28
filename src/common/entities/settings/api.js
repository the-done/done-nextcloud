import axios from "@nextcloud/axios";

export const fetchCustomSettingsList = async () => {
  const { data } = await axios.post("/getCustomSettingsList");

  return { data };
};

export const fetchUserCustomSettings = async ({ user_slug, setting_id }) => {
  const { data } = await axios.post("/getUserCustomSettings", { user_slug, setting_id });

  return { data };
};

export const fetchUserCustomSetting = async (settingId) => {
  const { data } = await axios.post("/getUserCustomSettings", { setting_id: settingId });

  return { data };
};

export const saveUserSettings = async (settings) => {
  const { data } = await axios.post("/saveUserSettings", { settings });

  return { data };
};

export const fetchAvailableLanguages = async () => {
  const { data } = await axios.get("/getAvailableLanguages");

  return { data };
}; 