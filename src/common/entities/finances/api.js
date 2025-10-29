/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

import axios from "@nextcloud/axios";

export const fetchPayments = async () => {
  const { data } = await axios.post("/module/finances", {
    method: 'getPayments'
  });

  return { data };
};

export const fetchPaymentBySlug = async ({ slug, slug_type }) => {
  const { data } = await axios.post("/module/finances", {
    slug,
    slug_type,
    method: 'getPayment'
  });

  return { data };
};

export const createPayment = async (payload) => {
  const { data } = await axios.post("/module/finances", {
    data: payload,
    method: 'addPayment'
  });

  return data;
};

export const updatePayment = async ({ slug, slug_type, data }) => {
  const response = await axios.post("/module/finances", {
    slug,
    slug_type,
    data,
    method: 'editPayment'
  });

  return response;
};

export const deletePayment = async ({ slug, slug_type }) => {
  const response = await axios.post("/module/finances", {
    slug,
    slug_type,
    method: 'deletePayment'
  });

  return response;
};

export const fetchPaymentTypes = async () => {
  const { data } = await axios.post("/module/finances", {
    method: 'getPaymentTypes'
  });

  return { data };
};

export const fetchPaymentsTableData = async () => {
  const { data } = await axios.post("/module/finances", {
    method: 'getPaymentsTableData'
  });

  return { data };
};

export const fetchSimpleUsers = async () => {
  const { data } = await axios.post("/module/finances", {
    method: 'getSimpleUsers'
  });

  return { data };
};

export const fetchCustomers = async () => {
  const { data } = await axios.post("/module/finances", {
    method: 'getCustomers'
  });

  return { data };
};
