import axios from "@nextcloud/axios";

// Basic
export const fetchTeams = async () => {
  const { data } = await axios.post("/module/teams", {
    method: 'getTeams'
  });

  return { data };
};

export const fetchTeamsTableData = async () => {
  const { data } = await axios.post("/module/teams", {
    method: 'getTeamsTableData'
  });

  return { data };
};

export const fetchTeamBySlug = async (slug) => {
  const { data } = await axios.post("/module/teams", {
    slug,
    method: 'getTeams'
  });

  return { data };
};

export const fetchTeamPublicDataBySlug = async ({ slug, slug_type }) => {
  const { data } = await axios.post("/module/teams", {
    slug,
    slug_type,
    method: 'getTeamsPublicData'
  });

  return { data };
};

export const createTeam = async (payload) => {
  const { data } = await axios.post("/module/teams", {
    data: payload,
    method: 'saveTeam'
  });

  return data;
};

export const updateTeam = async (slug, data) => {
  const response = await axios.post("/module/teams", {
    slug,
    data,
    method: 'saveTeam'
  });

  return response;
};

export const deleteTeam = async ({ slug, slug_type }) => {
  const response = await axios.post("/module/teams", {
    slug,
    slug_type,
    method: 'deleteTeam'
  });

  return response;
};

// Employee
export const getEmployeesInTeams = async (slug) => {
  const { data } = await axios.post("/module/teams", {
    team: { slug },
    method: 'getEmployeesInTeams'
  });

  return { data };
};

export const getEmployeeTeams = async (slug) => {
  const { data } = await axios.post("/module/teams", {
    user: { slug },
    method: 'getEmployeesInTeams'
  });

  return { data };
};

export const addEmployeeToTeam = async ({ team, user, role }) => {
  const { data } = await axios.post("/module/teams", {
    team,
    user,
    role,
    method: 'addEmployeeToTeam'
  });

  return { data };
};

export const removeEmployeeFromTeam = async ({ slug, slug_type }) => {
  const { data } = await axios.post("/module/teams", {
    slug,
    slug_type,
    method: 'removeEmployeeFromTeam'
  });

  return { data };
};

export const editEmployeeToTeam = async ({ slug, user, project, role }) => {
  const { data } = await axios.post("/module/teams", {
    slug,
    user,
    project,
    role,
    method: 'editEmployeeToTeam'
  });

  return { data };
};

// Projects
export const getProjectsInTeam = async (slug) => {
  const { data } = await axios.post("/module/teams", {
    team: { slug },
    method: 'getTeamsInProjects'
  });

  return { data };
};

export const addTeamToProject = async ({ team, project }) => {
  const { data } = await axios.post("/module/teams", {
    team,
    project,
    method: 'addTeamToProject'
  });

  return { data };
};

export const removeTeamFromProject = async ({ slug, slug_type }) => {
  const { data } = await axios.post("/module/teams", {
    slug,
    slug_type,
    method: 'removeTeamFromProject'
  });

  return { data };
};

// Directions
export const getDirectionsInTeam = async (slug) => {
  const { data } = await axios.post("/module/teams", {
    team: { slug },
    method: 'getTeamsInDirections'
  });

  return { data };
};

export const addTeamToDirection = async ({ team, direction }) => {
  const { data } = await axios.post("/module/teams", {
    team,
    direction,
    method: 'addTeamToDirection'
  });

  return { data };
};

export const removeTeamFromDirection = async ({ slug, slug_type }) => {
  const { data } = await axios.post("/module/teams", {
    slug,
    slug_type,
    method: 'removeTeamFromDirection'
  });

  return { data };
};
