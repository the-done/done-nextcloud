import axios from "@nextcloud/axios";

export const getFieldComments = async ({ source, field }) => {
  const { data } = await axios.post("/getFieldComments", { source, field });

  return { data };
};

export const getFieldCommentsBySource = async (source) => {
  const { data } = await axios.post("/getFieldCommentsBySource", { source });

  return data;
};

export const saveFieldComment = async ({
  source,
  field,
  comment,
  commentId,
}) => {
  const { data } = await axios.post("/saveFieldComment", {
    commentId,
    data: {
      source,
      field,
      comment,
    },
  });

  return { data };
};

export const deleteFieldComment = async ({ commentId }) => {
  const { data } = await axios.post("/deleteFieldComment", {
    commentId,
  });

  return { data };
};
