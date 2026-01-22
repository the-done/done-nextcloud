import { t } from "@nextcloud/l10n";
import { contextualTranslationsMixin } from "@/shared/lib/mixins/contextualTranslationsMixin";

export const contractTabsMixin = {
  mixins: [contextualTranslationsMixin],
  data() {
    return {
      tabs: [
        {
          key: "contract-table",
          label: t("done", "List"),
          to: {
            name: "finances-contract-table",
          },
          exact: true,
        },
        {
          key: "parameter-group-table",
          label: t("done", "Parameter groups"),
          to: {
            name: "finances-contract-parameter-group-table",
          },
          exact: true,
        },
      ],
    };
  },
};
