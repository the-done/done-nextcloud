import axios from "@nextcloud/axios";

export const fetchNextcloudUsers = async () => {
  const { data } = await axios.post("/getNextcloudUsersData");

  return { data };
};

export const fetchFreeNextcloudUsers = async () => {
  const { data } = await axios.post("/getFreeNextcloudUsers");

  return { data };
};