package com.unicorn.model;

/**
 * ローカルでコードと名前の組み合わせのマスタ情報を保持する
 * @author c1718
 *
 */
public class CodeDto {

	private String code;
	private String label;

	/**
	 * @return コード
	 */
	public String getCode() {
		return this.code;
	}

	/**
	 * @param code コード
	 */
	public void setCode(String code) {
		this.code = code;
	}

	/**
	 * @return 名前
	 */
	public String getLabel() {
		return label;
	}

	/**
	 * @param label 名前
	 */
	public void setLabel(String label) {
		this.label = label;
	}

	@Override
	public boolean equals(Object o) {
		if (this == o) {
			return true;
		}
		if (!(o instanceof CodeDto)) {
			return false;
		}
		CodeDto c = (CodeDto) o;
		return this.code.equals(c.getCode());
	}
}
