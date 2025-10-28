import axios from "@nextcloud/axios";

export const fetchCommonStatistics = async ({
  date_from,
  date_to,
  projects,
}) => {
  const { data } = await axios.post("/module/reports", {
    date_from,
    date_to,
    projects,
    method: 'getCommonStatistics'
  });

  return { data };
};

export const fetchProjectsStatistics = async ({ date_from, date_to, slug }) => {
  const {
    data: { data, totals },
  } = await axios.post("/module/reports", {
    date_from,
    date_to,
    slug,
    method: 'getProjectsStatistics'
  });

  return { data, totals };
};

export const fetchUsersCommonStatistics = async ({
  date_from,
  date_to,
  projects,
  teams,
  contract_types,
  directions,
}) => {
  const { data } = await axios.post("/module/reports", {
    date_from,
    date_to,
    projects,
    teams,
    contract_types,
    directions,
    method: 'getUsersCommonStatistics'
  });

  return { data };
};
